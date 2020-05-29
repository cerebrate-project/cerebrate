function populateAndLoadModal(url) {
    $.ajax({
        dataType:"html",
        cache: false,
        success:function (data, textStatus) {
            $("#mainModal").html(data);
            $("#mainModal").modal('show');
        },
        url:url,
    });
}

function executePagination(randomValue, url) {
    var target = '#table-container-' + randomValue
    $.ajax({
        dataType:"html",
        cache: false,
        success:function (data, textStatus) {
            $(target).html(data);
        },
        url:url,
    });
}
