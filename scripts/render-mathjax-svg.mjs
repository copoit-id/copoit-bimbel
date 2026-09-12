import { readFileSync } from 'node:fs';
import { liteAdaptor } from 'mathjax-full/js/adaptors/liteAdaptor.js';
import { RegisterHTMLHandler } from 'mathjax-full/js/handlers/html.js';
import { TeX } from 'mathjax-full/js/input/tex.js';
import { SVG } from 'mathjax-full/js/output/svg.js';
import { mathjax } from 'mathjax-full/js/mathjax.js';
import { Resvg } from '@resvg/resvg-js';

const input = JSON.parse(readFileSync(0, 'utf8'));
const adaptor = liteAdaptor();
RegisterHTMLHandler(adaptor);

const tex = new TeX({
    packages: ['base', 'ams', 'newcommand', 'noundefined'],
});
const svg = new SVG({ fontCache: 'none' });
const html = mathjax.document('', { InputJax: tex, OutputJax: svg });

const output = input.map(({ latex, display = false }) => {
    try {
        const math = html.convert(String(latex), { display: Boolean(display) });
        const svgMarkup = adaptor.outerHTML(adaptor.firstChild(math));
        const widthEx = Number(svgMarkup.match(/\bwidth="([\d.]+)ex"/)?.[1] ?? 1);
        const heightEx = Number(svgMarkup.match(/\bheight="([\d.]+)ex"/)?.[1] ?? 1);
        const verticalAlignEx = Number(svgMarkup.match(/vertical-align:\s*([\-\d.]+)ex/)?.[1] ?? 0);
        const png = new Resvg(svgMarkup, {
            background: 'rgba(255,255,255,0)',
            fitTo: { mode: 'zoom', value: 3 },
        }).render().asPng().toString('base64');

        return { png, widthEx, heightEx, verticalAlignEx };
    } catch {
        return null;
    }
});

process.stdout.write(JSON.stringify(output));
