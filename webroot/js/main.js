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

var UI
$(document).ready(() => {
    if (typeof UIFactory !== "undefined") {
        UI = new UIFactory()
    }
})

class AppElement {
    constructor(appData) {
        if (appData.data !== undefined) {
            for (const dataName in appData.data) {
                this[dataName] = appData.data[dataName]
            }
        }
        if (appData.methods !== undefined) {
            for (const methodName in appData.methods) {
                this[methodName] = appData.methods[methodName]
            }
        }
        if (appData.mounted !== undefined) {
            appData.mounted()
        }
        const $script = $(document.currentScript)
        const seed = $(document.currentScript).attr('element-scoped').split('_')[1];
        const $rootElement = $script.closest(`section[data-scoped="${seed}"]`)
        this.el = $rootElement[0]
        this.$el = $rootElement
        $rootElement.data('appElement', this) // register app element on the root element node
    }
}