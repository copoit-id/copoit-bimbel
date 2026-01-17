import http from 'k6/http';
import { check, fail, group, sleep } from 'k6';
import { parseHTML } from 'k6/html';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const USER_EMAIL = __ENV.USER_EMAIL || 'admin@copoit.com';
const USER_PASSWORD = __ENV.USER_PASSWORD || 'Passw0rd';
const PACKAGE_ID = __ENV.PACKAGE_ID || '1';
const TRYOUT_ID = __ENV.TRYOUT_ID || '1';
const QUESTION_NUMBER = __ENV.QUESTION_NUMBER || '1';

export const options = {
    stages: [
        { duration: '20s', target: 0 },
        { duration: '20s', target: 5 },
        { duration: '20s', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<1500'],
        http_req_failed: ['rate<0.02'],
    },
};

function extractHiddenValue(doc, selector, fallback = null) {
    const element = doc.find(selector).first();
    if (!element) {
        return fallback;
    }
    return element.attr('value') || fallback;
}

function loginSequence() {
    return group('Login', () => {
        const loginPage = http.get(`${BASE_URL}/login`);
        check(loginPage, {
            'login page 200': (res) => res.status === 200,
        });

        const loginDoc = parseHTML(loginPage.body);
        const csrfToken = extractHiddenValue(loginDoc, 'input[name="_token"]');

        if (!csrfToken) {
            fail('Unable to extract CSRF token from login page');
        }

        const payload = {
            _token: csrfToken,
            email: USER_EMAIL,
            password: USER_PASSWORD,
        };

        const loginRes = http.post(`${BASE_URL}/login`, payload, {
            redirects: 0,
        });

        check(loginRes, {
            'login success (302/200)': (res) => [200, 302].includes(res.status),
        });

        if (loginRes.status === 302 && loginRes.headers.Location) {
            http.get(loginRes.headers.Location.startsWith('http') ? loginRes.headers.Location : `${BASE_URL}${loginRes.headers.Location}`);
        }
    });
}

function hitDashboard() {
    return group('Dashboard', () => {
        const res = http.get(`${BASE_URL}/user/dashboard`);
        check(res, {
            'dashboard reachable': (r) => r.status === 200,
        });
    });
}

function tryoutFlow() {
    return group('Tryout flow', () => {
        const lobbyRes = http.get(`${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/lobby`);
        check(lobbyRes, { 'lobby ok (200/302)': (r) => [200, 302].includes(r.status) });

        const questionUrl = `${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/tryout/${QUESTION_NUMBER}`;
        const questionRes = http.get(questionUrl);
        check(questionRes, { 'question page 200': (r) => r.status === 200 });

        const questionDoc = parseHTML(questionRes.body);
        const questionId = extractHiddenValue(questionDoc, 'form#answerForm input[name="question_id"]');
        const csrfToken = extractHiddenValue(questionDoc, 'form#answerForm input[name="_token"]');
        const optionId = extractHiddenValue(questionDoc, 'form#answerForm input[name="answer_option"]');

        if (!questionId || !csrfToken || !optionId) {
            fail('Missing question_id / csrf / option_id on tryout page');
        }

        const saveUrl = `${BASE_URL}/user/tryout/${PACKAGE_ID}/${TRYOUT_ID}/tryout/${QUESTION_NUMBER}/save`;
        const payload = JSON.stringify({
            question_id: Number(questionId),
            option_id: Number(optionId),
        });

        const saveRes = http.post(saveUrl, payload, {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            tags: { name: 'save-answer' },
        });

        let saveJson = null;
        try {
            saveJson = saveRes.json();
        } catch (error) {
            // Non-JSON response fallback
        }

        check(saveRes, {
            'answer saved or already stored': (r) =>
                r.status === 200 && (!!saveJson?.success || !saveJson?.error),
        });
    });
}

export default function () {
    loginSequence();
    hitDashboard();
    tryoutFlow();
    sleep(1);
}
