function executePagination(randomValue, url) {
    UI.reload(url, $(`#table-container-${randomValue}`), $(`#table-container-${randomValue} table.table`))
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

function toggleAllAttributeCheckboxes(clicked) {
    let $clicked = $(clicked)
    let $table = $clicked.closest('table')
    let $inputs = $table.find('input.selectable_row')
    $inputs.prop('checked', $clicked.prop('checked'))
}

function testConnection(id) {
    $container = $(`#connection_test_${id}`)
    UI.overlayUntilResolve(
        $container[0],
        AJAXApi.quickFetchJSON(`/broods/testConnection/${id}`),
        {text: 'Running test'}
    ).then(result => {
        const $testResult = attachTestConnectionResultHtml(result, $container)
        $(`#connection_test_${id}`).append($testResult)
    })
    .catch((error) => {
        const $testResult = attachTestConnectionResultHtml(error.message, $container)
        $(`#connection_test_${id}`).append($testResult)
    })
}

function attachTestConnectionResultHtml(result, $container) {
    function getKVHtml(key, value, valueClasses=[], extraValue='') {
        return $('<div/>').append(
            $('<strong/>').text(key + ': '),
            $('<span/>').addClass(valueClasses).text(value),
            $('<span/>').text(extraValue.length > 0 ? ` (${extraValue})` : '')
        )
    }
    $container.find('div.tester-result').remove()
    $testResultDiv = $('<div class="tester-result"></div>');
    if (typeof result !== 'object') {
        $testResultDiv.append(getKVHtml('Internal error', result, ['text-danger font-weight-bold']))
    } else {
        if (result['error']) {
            $testResultDiv.append(
                getKVHtml('Status', 'OK', ['text-danger'], `${result['ping']} ms`),
                getKVHtml('Status', `Error: ${result['error']}`, ['text-danger']),
                getKVHtml('Reason', result['reason'], ['text-danger'])
            )
        } else {
            const canSync = result['response']['role']['perm_admin'] || result['response']['role']['perm_sync'];
            $testResultDiv.append(
                getKVHtml('Status', 'OK', ['text-success'], `${result['ping']} ms`),
                getKVHtml('Remote', `${result['response']['application']} v${result['response']['version']}`),
                getKVHtml('User', result['response']['user'], [], result['response']['role']['name']),
                getKVHtml('Sync permission', (canSync ? 'Yes' : 'No'), [(canSync ? 'text-success' : 'text-danger')]),
            )
        }
    }
    return $testResultDiv
}

function syntaxHighlightJson(json, indent) {
    if (indent === undefined) {
        indent = 2;
    }
    if (typeof json == 'string') {
        json = JSON.parse(json);
    }
    json = JSON.stringify(json, undefined, indent);
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/(?:\r\n|\r|\n)/g, '<br>').replace(/ /g, '&nbsp;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'text-info';
        if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                        cls = 'text-primary';
                } else {
                        cls = '';
                }
        } else if (/true|false/.test(match)) {
                cls = 'text-info';
        } else if (/null/.test(match)) {
                cls = 'text-danger';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

function getTextColour(hex) {
    if (hex === undefined || hex.length == 0) {
        return 'black'
    }
    hex = hex.slice(1)
    var r = parseInt(hex.substring(0,2), 16)
    var g = parseInt(hex.substring(2,4), 16)
    var b = parseInt(hex.substring(4,6), 16)
    var avg = ((2 * r) + b + (3 * g))/6
    if (avg < 128) {
        return 'white'
    } else {
        return 'black'
    }
}

function createTagPicker(clicked) {

    function templateTag(state) {
        if (!state.id) {
            return state.text;
        }
        if (state.colour === undefined) {
            state.colour = $(state.element).data('colour')
        }
        return HtmlHelper.tag(state)
    }

    const $clicked = $(clicked)
    const $container = $clicked.closest('.tag-container')
    $('.picker-container').remove()
    const $pickerContainer = $('<div></div>').addClass(['picker-container', 'd-flex'])
    const $select = $container.find('select.tag-input').removeClass('d-none').addClass('flex-grow-1')
    const $saveButton = $('<button></button>').addClass(['btn btn-primary btn-sm', 'align-self-start'])
        .append($('<span></span>').text('Save').prepend($('<i></i>').addClass('fa fa-save mr-1')))
    const $cancelButton = $('<button></button>').addClass(['btn btn-secondary btn-sm', 'align-self-start'])
        .append($('<span></span>').text('Cancel').prepend($('<i></i>').addClass('fa fa-times mr-1')))
        .click(function() {
            $select.appendTo($container)
            $pickerContainer.remove()
        })
    const $buttons = $('<span></span>').addClass(['picker-action', 'btn-group']).append($saveButton, $cancelButton)
    $select.prependTo($pickerContainer)
    $pickerContainer.append($buttons)
    $container.parent().append($pickerContainer)
    $select.select2({
        placeholder: 'Pick a tag',
        tags: true,
        templateResult: templateTag,
        templateSelection: templateTag,
    })
}


var UI
$(document).ready(() => {
    if (typeof UIFactory !== "undefined") {
        UI = new UIFactory()
    }
})
