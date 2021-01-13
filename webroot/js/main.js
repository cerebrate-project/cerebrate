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

function executeStateDependencyChecks(dependenceSourceSelector) {
    if (dependenceSourceSelector === undefined) {
        var tempSelector = "[data-dependence-source]";
    } else {
        var tempSelector = '*[data-dependence-source="' + dependenceSourceSelector + '"]';
    }
    $(tempSelector).each(function(index, dependent) {
        var dependenceSource = $(dependent).data('dependence-source');
        if ($(dependent).data('dependence-option') === $(dependenceSource).val()) {
            $(dependent).parent().parent().removeClass('d-none');
        } else {
            $(dependent).parent().parent().addClass('d-none');
        }
    });
}

function testConnection(id) {
    $.ajax({
        url: '/broods/testConnection/' + id,
        type: 'GET',
        beforeSend: function () {
            $("#connection_test_" + id).html('Running test...');
        },
        error: function(){
            $("#connection_test_" + id).html('<span class="red bold">Internal error</span>');
        },
        success: function(result) {
            var html = '';
            if (result['error']) {
                html += '<strong>Status</strong>: <span class="text-danger">OK</span> (' + $("<span>").text(result['ping']).html() + ' ms)<br />';
                html += '<strong>Status</strong>: <span class="text-danger">Error: ' + result['error'] + '</span>';
                html += '<strong>Reason</strong>: <span class="text-danger">' + result['reason'] + '</span>';
            } else {
                html += '<strong>Status</strong>: <span class="text-success">OK</span> (' + $("<span>").text(result['ping']).html() + ' ms)<br />';
                html += '<strong>Remote</strong>: ' + $("<span>").text(result['response']['application']).html() + ' v' + $("<span>").text(result['response']['version']).html() + '<br />';
                html += '<strong>User</strong>: ' + $("<span>").text(result['response']['user']).html() + ' (' + $("<span>").text(result['response']['role']['name']).html() + ')' + '<br />';
                var canSync = result['response']['role']['perm_admin'] || result['response']['role']['perm_sync'];
                if (canSync) {
                    html += '<strong>Sync permission</strong>: <span class="text-success">Yes</span><br />';
                } else {
                    html += '<strong>Sync permission</strong>: <span class="text-danger">No</span><br />';
                }
            }
            $("#connection_test_" + id).html(html);
        }
    })
}
