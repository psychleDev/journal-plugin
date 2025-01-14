jQuery(document).ready(function ($) {
    const shareButton = $('.share-entry');
    const shareContainer = $('#share-entry-container');

    if (shareContainer.length) {
        const currentDay = parseInt($('.journal-container h2').text().match(/\d+/)[0]);
        const prompt = $('.prompt').text();

        const shareRoot = ReactDOM.createRoot(shareContainer[0]);
        shareRoot.render(
            React.createElement(ShareEntry, {
                entryId: currentDay,
                prompt: prompt
            })
        );
    }
});