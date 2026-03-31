{{-- MathJax for rendering LaTeX math formulas --}}
<script>
    window.MathJax = {
        skipStartupTypeset: true,
        tex2jax: {
            inlineMath: [['$', '$'], ['\\(', '\\)']],
            displayMath: [['$$', '$$'], ['\\[', '\\]']],
            processEscapes: true
        },
        messageStyle: 'none',
        showMathMenu: false,
        'HTML-CSS': {
            availableFonts: ['TeX']
        }
    };
</script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=TeX-AMS_HTML"></script>
<script>
    window.renderMathJax = function () {
        if (window.MathJax) {
            if (window.MathJax.Hub) {
                MathJax.Hub.Queue(['Typeset', MathJax.Hub]);
            } else if (window.MathJax.typesetPromise) {
                MathJax.typesetPromise();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.renderMathJax();
    });
</script>
