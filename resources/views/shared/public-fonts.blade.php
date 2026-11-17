@php($fontUrl = $fontUrl ?? 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap')
<script data-font-url="{{ $fontUrl }}">
    (() => {
        const fontUrl = document.currentScript.dataset.fontUrl;
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        const slow = connection && (connection.saveData || (connection.downlink > 0 && connection.downlink < 1)
            || ['slow-2g', '2g'].includes(connection.effectiveType));
        if (slow) {
            document.documentElement.classList.add('data-saver');
        }
        const fonts = document.createElement('link');
        fonts.rel = 'stylesheet';
        fonts.href = fontUrl;
        fonts.media = 'print';
        fonts.onload = () => { fonts.media = 'all'; };
        document.head.appendChild(fonts);
    })();
</script>
<noscript><link rel="stylesheet" href="{{ $fontUrl }}"></noscript>
