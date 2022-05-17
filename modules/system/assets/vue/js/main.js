
// Configure bluebird
$(document).ready(function () {
    Promise.config({
        cancellation: true
    });

    Queue.configure(Promise);
});
