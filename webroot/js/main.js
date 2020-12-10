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

var AjaxApi, UI
$(document).ready(() => {
    AjaxApi = new AJAXApi()
    UI = new UIFactory()
})

function reloadElement(url, $container, $statusNode=null) {
    if (!$statusNode) {
        $statusNode = $container
    }
    tmpApi = new AJAXApi({
        statusNode: $statusNode[0],
    })
    tmpApi.fetchURL(url).then((data) => {
        $container.html(data)
    })
}