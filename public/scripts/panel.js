$(document).ready(function() {
    $('#deleteForm').on('submit', function(e) {
        e.preventDefault();
        $('#confirmationModal').removeClass('hidden');
    });

    $('#cancelBtn').on('click', function() {
        $('#confirmationModal').addClass('hidden');
    });

    $('#confirmBtn').on('click', function() {
        $('#confirmationModal').addClass('hidden');
        $('#deleteForm')[0].submit();
    });

});
