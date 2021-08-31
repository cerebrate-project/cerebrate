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

    function closePicker($select, $container) {
        $select.appendTo($container)
        $container.parent().find('.picker-container').remove()
    }

    function getEditableButtons($select, $container) {
        const $saveButton = $('<button></button>').addClass(['btn btn-primary btn-sm', 'align-self-start']).attr('type', 'button')
        .append($('<span></span>').text('Save').prepend($('<i></i>').addClass('fa fa-save mr-1')))
        .click(function() {
            const tags = $select.select2('data').map(tag => tag.text)
            addTags($select.data('url'), tags, $(this))
        })
        const $cancelButton = $('<button></button>').addClass(['btn btn-secondary btn-sm', 'align-self-start']).attr('type', 'button')
            .append($('<span></span>').text('Cancel').prepend($('<i></i>').addClass('fa fa-times mr-1')))
            .click(function() {
                closePicker($select, $container)
            })
        const $buttons = $('<span></span>').addClass(['picker-action', 'btn-group']).append($saveButton, $cancelButton)
        return $buttons
    }

    const $clicked = $(clicked)
    const $container = $clicked.closest('.tag-container')
    const $select = $container.parent().find('select.tag-input').removeClass('d-none')//.addClass('flex-grow-1')
    closePicker($select, $container)
    const $pickerContainer = $('<div></div>').addClass(['picker-container', 'd-flex'])
    
    $select.prependTo($pickerContainer)
    $pickerContainer.append(getEditableButtons($select, $container))
    $container.parent().append($pickerContainer)
    initSelect2Picker($select)
}

function deleteTag(url, tag, clicked) {
    const data = {
        tag_list: tag
    }
    const $statusNode = $(clicked).closest('.tag')
    const APIOptions = {
        statusNode: $statusNode,
        skipFeedback: true,
    }
    return AJAXApi.quickFetchAndPostForm(url, data, APIOptions).then((result) => {
        let $container = $statusNode.closest('.tag-container-wrapper')
        refreshTagList(result, $container).then(($tagContainer) => {
            $container = $tagContainer // old container might not exist anymore since it was replaced after the refresh
        })
        const theToast = UI.toast({
            variant: 'success',
            title: result.message,
            bodyHtml: $('<div/>').append(
                $('<span/>').text('Cancel untag operation.'),
                $('<button/>').addClass(['btn', 'btn-primary', 'btn-sm', 'ml-3']).text('Restore tag').click(function() {
                    const split = url.split('/')
                    const controllerName = split[1]
                    const id = split[3]
                    const urlRetag = `/${controllerName}/tag/${id}`
                    addTags(urlRetag, [tag], $container.find('.tag-container')).then(() => {
                        theToast.removeToast()
                    })
                }),
            ),
        })
    }).catch((e) => {})
}

function addTags(url, tags, $statusNode) {
    const data = {
        tag_list: tags
    }
    const APIOptions = {
        statusNode: $statusNode
    }
    return AJAXApi.quickFetchAndPostForm(url, data, APIOptions).then((result) => {
        const $container = $statusNode.closest('.tag-container-wrapper')
        refreshTagList(result, $container)
    }).catch((e) => {})
}

function refreshTagList(result, $container) {
    const controllerName = result.url.split('/')[1]
    const entityId = result.data.id
    const url = `/${controllerName}/viewTags/${entityId}`
    return UI.reload(url, $container)
}

function initSelect2Pickers() {
    $('select.tag-input').each(function() {
        if (!$(this).hasClass("select2-hidden-accessible")) {
            initSelect2Picker($(this))
        }
    })
}

function initSelect2Picker($select) {

    function templateTag(state, $select) {
        if (!state.id) {
            return state.label;
        }
        if (state.colour === undefined) {
            state.colour = $(state.element).data('colour')
        }
        if ($select !== undefined && state.text[0] === '!') {
            // fetch corresponding tag and set colors?
            // const baseTag = state.text.slice(1)
            // const existingBaseTag = $select.find('option').filter(function() {
            //     return $(this).val() === baseTag
            // })
            // if (existingBaseTag.length > 0) {
            //     state.colour = existingBaseTag.data('colour')
            //     state.text = baseTag
            // }
        }
        return HtmlHelper.tag(state)
    }

    $select.select2({
        placeholder: 'Pick a tag',
        tags: true,
        width: '100%',
        templateResult: (state) => templateTag(state),
        templateSelection: (state) => templateTag(state, $select),
    })
}


var UI
$(document).ready(() => {
    if (typeof UIFactory !== "undefined") {
        UI = new UIFactory()
    }
})
