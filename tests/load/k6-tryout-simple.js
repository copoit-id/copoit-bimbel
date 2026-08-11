import http from 'k6/http';
import { check, fail, group, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Counter, Trend } from 'k6/metrics';
import { parseHTML } from 'k6/html';
import exec from 'k6/execution';

/*
 * End-to-end tryout load test.
 *
 * Each VU uses one unique user, opens the lobby, starts the tryout, saves an
 * answer for EVERY rendered question, then submits the attempt. This writes
 * real UserAnswer/UserAnswerDetail data, so only run it against a dedicated
 * test tryout and test accounts. ARRIVAL_WINDOW_SECONDS and the delay options
 * can spread this flow to resemble normal participant behaviour.
 *
 * See tests/load/README.md for the required users.csv and run commands.
 */

const BASE_URL = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const PACKAGE_ID = __ENV.PACKAGE_ID || 'free';
const TRYOUT_ID = requirePositiveInteger(__ENV.TRYOUT_ID, 'TRYOUT_ID');
const EXPECTED_QUESTION_COUNT = optionalPositiveInteger(__ENV.QUESTION_COUNT, 'QUESTION_COUNT');
const ANSWER_INTERVAL_SECONDS = nonNegativeNumber(__ENV.ANSWER_INTERVAL_SECONDS || '0', 'ANSWER_INTERVAL_SECONDS');
const ANSWER_INTERVAL_JITTER_SECONDS = nonNegativeNumber(
    __ENV.ANSWER_INTERVAL_JITTER_SECONDS || '0',
    'ANSWER_INTERVAL_JITTER_SECONDS'
);
const ARRIVAL_WINDOW_SECONDS = nonNegativeNumber(
    __ENV.ARRIVAL_WINDOW_SECONDS || '0',
    'ARRIVAL_WINDOW_SECONDS'
);
const START_DELAY_MIN_SECONDS = nonNegativeNumber(
    __ENV.START_DELAY_MIN_SECONDS || '0',
    'START_DELAY_MIN_SECONDS'
);
const START_DELAY_MAX_SECONDS = nonNegativeNumber(
    __ENV.START_DELAY_MAX_SECONDS || '0',
    'START_DELAY_MAX_SECONDS'
);
const FINISH_TRYOUT = parseBoolean(__ENV.FINISH_TRYOUT, true);
const TEXT_ANSWER = __ENV.TEXT_ANSWER || 'Jawaban simulasi beban tryout';
const USERS_FILE = __ENV.USERS_FILE || './users.csv';
const MAX_DURATION = __ENV.MAX_DURATION || '30s';
const GRACEFUL_STOP = __ENV.GRACEFUL_STOP || '0s';

const answersSaved = new Counter('tryout_answers_saved');
const answersFailed = new Counter('tryout_answers_failed');
const attemptsCompleted = new Counter('tryout_attempts_completed');
const saveAnswerDuration = new Trend('tryout_save_answer_duration', true);

const users = new SharedArray('tryout-test-users', () => parseUsersCsv(open(USERS_FILE)));
const VUS = optionalPositiveInteger(__ENV.VUS, 'VUS') || users.length;

if (users.length === 0) {
    throw new Error(`No test users found in ${USERS_FILE}. Copy users.example.csv to users.csv first.`);
}

if (users.length < VUS) {
    throw new Error(`VUS=${VUS} requires ${VUS} unique accounts, but ${USERS_FILE} only contains ${users.length}.`);
}

if (START_DELAY_MAX_SECONDS < START_DELAY_MIN_SECONDS) {
    throw new Error('START_DELAY_MAX_SECONDS must be greater than or equal to START_DELAY_MIN_SECONDS.');
}

export const options = {
    scenarios: {
        tryout_burst: {
            executor: 'per-vu-iterations',
            vus: VUS,
            iterations: 1,
            maxDuration: MAX_DURATION,
            gracefulStop: GRACEFUL_STOP,
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<2000'],
        checks: ['rate>0.99'],
    },
};

function requirePositiveInteger(value, name) {
    const parsed = Number.parseInt(value, 10);

    if (!Number.isInteger(parsed) || parsed < 1) {
        throw new Error(`${name} must be a positive integer.`);
    }

    return parsed;
}

function optionalPositiveInteger(value, name) {
    if (value === undefined || value === null || value === '') {
        return null;
    }

    return requirePositiveInteger(value, name);
}

function nonNegativeNumber(value, name) {
    const parsed = Number(value);

    if (!Number.isFinite(parsed) || parsed < 0) {
        throw new Error(`${name} must be a non-negative number.`);
    }

    return parsed;
}

function parseBoolean(value, defaultValue) {
    if (value === undefined || value === null || value === '') {
        return defaultValue;
    }

    return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
}

function randomBetween(minimum, maximum) {
    if (maximum <= minimum) {
        return minimum;
    }

    return minimum + (Math.random() * (maximum - minimum));
}

function parseUsersCsv(content) {
    const usersFromCsv = [];
    const seenEmails = {};

    content.split(/\r?\n/).forEach((line, index) => {
        const trimmed = line.trim();

        if (trimmed === '' || trimmed.startsWith('#')) {
            return;
        }

        const values = trimmed.split(',').map((value) => value.trim());
        const email = values[0];
        const password = values[1];

        if (email.toLowerCase() === 'email' && password.toLowerCase() === 'password') {
            return;
        }

        if (!email || !password || values.length !== 2) {
            throw new Error(`Invalid CSV row ${index + 1} in ${USERS_FILE}. Expected: email,password`);
        }

        const normalizedEmail = email.toLowerCase();
        if (seenEmails[normalizedEmail]) {
            throw new Error(`Duplicate email on CSV row ${index + 1}: ${email}. Every VU must use a unique account.`);
        }

        seenEmails[normalizedEmail] = true;

        usersFromCsv.push({ email, password });
    });

    return usersFromCsv;
}

function extractValue(selection) {
    return selection.first().attr('value') || null;
}

function responseJson(response) {
    try {
        return response.json();
    } catch (_) {
        return null;
    }
}

function absoluteUrl(path) {
    return path.startsWith('http') ? path : `${BASE_URL}${path}`;
}

function requestOptions(name, extra = {}) {
    return {
        tags: { name },
        ...extra,
    };
}

function login(user) {
    return group('01 - login', () => {
        const loginPage = http.get(`${BASE_URL}/login`, requestOptions('tryout-login-page'));
        const loginPageOk = check(loginPage, {
            'login page is available': (response) => response.status === 200,
        });

        if (!loginPageOk) {
            return false;
        }

        const csrfToken = extractValue(parseHTML(loginPage.body).find('input[name="_token"]'));
        if (!csrfToken) {
            check(loginPage, {
                'login page includes a CSRF token': () => false,
            });

            return false;
        }

        const response = http.post(`${BASE_URL}/login`, {
            _token: csrfToken,
            email: user.email,
            password: user.password,
        }, requestOptions('tryout-login', { redirects: 0 }));

        const location = response.headers.Location || response.headers.location || '';
        const loginOk = check(response, {
            'login redirects to authenticated area': (result) =>
                [302, 303].includes(result.status) && location !== '' && !location.includes('/login'),
        });

        if (!loginOk) {
            return false;
        }

        const redirectResponse = http.get(absoluteUrl(location), requestOptions('tryout-login-redirect'));

        return check(redirectResponse, {
            'authenticated redirect is reachable': (result) => result.status >= 200 && result.status < 400,
        });
    });
}

function openTryout() {
    return group('02 - lobby and start tryout', () => {
        const lobbyUrl = `${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/lobby`;
        const lobby = http.get(lobbyUrl, requestOptions('tryout-lobby'));
        const lobbyOk = check(lobby, {
            'lobby is available to this user': (response) => response.status === 200,
        });

        if (!lobbyOk) {
            return null;
        }

        const startDelay = randomBetween(START_DELAY_MIN_SECONDS, START_DELAY_MAX_SECONDS);
        if (startDelay > 0) {
            sleep(startDelay);
        }

        const pageUrl = `${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/tryout/1`;
        const tryoutPage = http.get(pageUrl, requestOptions('tryout-start'));
        const tryoutPageOk = check(tryoutPage, {
            'tryout page starts successfully': (response) => response.status === 200,
        });

        if (!tryoutPageOk) {
            return null;
        }

        return tryoutPage;
    });
}

function firstNonEmptyOptionValue(select) {
    let value = null;

    select.find('option').each((_, option) => {
        if (value !== null) {
            return;
        }

        const candidate = option.selection().attr('value');
        if (candidate) {
            value = candidate;
        }
    });

    return value;
}

function buildPayload(wrapper, questionId, questionType) {
    if (['multiple_choice', 'true_false'].includes(questionType)) {
        const optionId = extractValue(wrapper.find('input.answer-input'));
        if (!optionId) {
            fail(`Question ${questionId} has no selectable option.`);
        }

        return { question_id: Number(questionId), option_id: Number(optionId) };
    }

    if (questionType === 'multiple_answer') {
        const optionId = extractValue(wrapper.find('input.answer-input'));
        if (!optionId) {
            fail(`Question ${questionId} has no selectable option.`);
        }

        return { question_id: Number(questionId), option_ids: [Number(optionId)] };
    }

    if (questionType === 'matching') {
        const matchingAnswers = {};

        wrapper.find('select.matching-select').each((_, select) => {
            const selectSelection = select.selection();
            const left = selectSelection.attr('data-left');
            const selected = firstNonEmptyOptionValue(selectSelection);

            if (!left || !selected) {
                fail(`Question ${questionId} has an incomplete matching configuration.`);
            }

            matchingAnswers[left] = selected;
        });

        if (Object.keys(matchingAnswers).length === 0) {
            fail(`Question ${questionId} has no matching pairs.`);
        }

        return { question_id: Number(questionId), matching_answers: matchingAnswers };
    }

    if (questionType === 'multiple_true_false') {
        const mtfAnswers = {};

        wrapper.find('input.mtf-radio').each((_, radio) => {
            const radioSelection = radio.selection();
            const statementId = radioSelection.attr('data-statement-id');
            if (statementId && mtfAnswers[statementId] === undefined) {
                mtfAnswers[statementId] = radioSelection.attr('value');
            }
        });

        if (Object.keys(mtfAnswers).length === 0) {
            fail(`Question ${questionId} has no true/false statements.`);
        }

        return { question_id: Number(questionId), mtf_answers: mtfAnswers };
    }

    if (['short_answer', 'essay'].includes(questionType)) {
        return { question_id: Number(questionId), answer_text: TEXT_ANSWER };
    }

    if (questionType === 'audio') {
        fail(`Question ${questionId} requires audio upload. It cannot be truthfully answered by this HTTP script.`);
    }

    fail(`Question ${questionId} has unsupported type "${questionType}".`);
    return null;
}

function extractQuestions(tryoutPage) {
    const document = parseHTML(tryoutPage.body);
    const csrfToken = extractValue(document.find('form.answer-form input[name="_token"]'));
    const attemptToken = extractValue(document.find('#finishForm input[name="attempt_token"]'));
    const questions = [];

    if (!csrfToken || !attemptToken) {
        fail('Tryout page is missing its CSRF token or attempt token.');
    }

    document.find('.question-wrapper').each((_, wrapper) => {
        const wrapperSelection = wrapper.selection();
        const questionId = wrapperSelection.attr('data-question-id');
        const questionNumber = wrapperSelection.attr('data-number');
        const questionType = wrapperSelection.attr('data-question-type');

        if (!questionId || !questionNumber || !questionType) {
            fail('A rendered question is missing required data attributes.');
        }

        questions.push({
            id: Number(questionId),
            number: Number(questionNumber),
            payload: buildPayload(wrapperSelection, questionId, questionType),
        });
    });

    if (questions.length === 0) {
        fail('No questions were found on the started tryout page.');
    }

    if (EXPECTED_QUESTION_COUNT && questions.length !== EXPECTED_QUESTION_COUNT) {
        fail(`Expected ${EXPECTED_QUESTION_COUNT} questions, but the tryout rendered ${questions.length}.`);
    }

    return { csrfToken, attemptToken, questions };
}

function saveEveryAnswer(questionSet) {
    return group('03 - save every answer', () => {
        for (let index = 0; index < questionSet.questions.length; index += 1) {
            const question = questionSet.questions[index];
            const url = `${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/tryout/${question.number}/save`;
            const response = http.post(url, JSON.stringify(question.payload), requestOptions('tryout-save-answer', {
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': questionSet.csrfToken,
                },
            }));
            const body = responseJson(response);
            const saved = check(response, {
                'answer is saved successfully': (result) => result.status === 200 && body && body.success === true,
            });

            saveAnswerDuration.add(response.timings.duration);

            if (!saved) {
                answersFailed.add(1);

                return false;
            }

            answersSaved.add(1);

            if (index < questionSet.questions.length - 1) {
                const answerDelay = ANSWER_INTERVAL_SECONDS
                    + (ANSWER_INTERVAL_JITTER_SECONDS > 0
                        ? Math.random() * ANSWER_INTERVAL_JITTER_SECONDS
                        : 0);

                if (answerDelay > 0) {
                    sleep(answerDelay);
                }
            }
        }

        return true;
    });
}

function finishTryout(questionSet) {
    if (!FINISH_TRYOUT) {
        return true;
    }

    return group('04 - submit tryout', () => {
        const url = `${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/finish`;
        const response = http.post(url, {
            _token: questionSet.csrfToken,
            answers_payload: '[]',
            attempt_token: questionSet.attemptToken,
            current_question_number: questionSet.questions.length,
        }, requestOptions('tryout-finish', {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': questionSet.csrfToken,
            },
        }));
        const body = responseJson(response);
        const completed = check(response, {
            'tryout is submitted successfully': (result) => result.status === 200 && body && body.success === true,
        });

        if (!completed) {
            return false;
        }

        attemptsCompleted.add(1);

        return true;
    });
}

export default function () {
    const user = users[exec.vu.idInTest - 1];

    if (!user) {
        fail(`No unique account assigned to VU ${exec.vu.idInTest}.`);
    }

    const arrivalDelay = ARRIVAL_WINDOW_SECONDS > 0
        ? Math.random() * ARRIVAL_WINDOW_SECONDS
        : 0;
    if (arrivalDelay > 0) {
        sleep(arrivalDelay);
    }

    if (!login(user)) {
        return;
    }

    const tryoutPage = openTryout();
    if (!tryoutPage) {
        return;
    }

    const questionSet = extractQuestions(tryoutPage);

    if (!saveEveryAnswer(questionSet)) {
        return;
    }

    finishTryout(questionSet);
}
