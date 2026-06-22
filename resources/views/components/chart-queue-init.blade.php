<script>
(function () {
    const queue = [];

    window.whenChartsReady = function (callback) {
        if (typeof callback !== 'function') {
            return;
        }

        if (window.LedgerCharts && window.Chart) {
            try {
                callback();
            } catch (error) {
                console.error('Chart initialization failed:', error);
            }

            return;
        }

        queue.push(callback);
    };

    window.flushChartQueue = function () {
        while (queue.length > 0) {
            const callback = queue.shift();

            try {
                callback?.();
            } catch (error) {
                console.error('Chart initialization failed:', error);
            }
        }
    };
})();
</script>
