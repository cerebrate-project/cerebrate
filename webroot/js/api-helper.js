class AJAXApi {
    static genericRequestHeaders = {
        'X-Requested-With': 'XMLHttpRequest'
    };
    static genericRequestConfigGET = {
        headers: new Headers(Object.assign({}, AJAXApi.genericRequestHeaders))
    }
    static genericRequestConfigPOST = {
        headers: new Headers(Object.assign({}, AJAXApi.genericRequestHeaders)),
        redirect: 'manual',
        method: 'POST',
    }
    static renderHTMLOnFailureHeader = {
        name: 'X-Request-HTML-On-Failure',
        value: '1'
    }

    static defaultOptions = {
        provideFeedback: true,
        statusNode: false,
        renderedHTMLOnFailureRequested: false,
        errorToast: {
            delay: 10000
        }
    }
    options = {}
    loadingOverlay = false

    constructor(options) {
        this.mergeOptions(AJAXApi.defaultOptions)
        this.mergeOptions(options)
    }

    provideFeedback(toastOptions, isError=false, skip=false) {
        const alteredToastOptions = isError ? Object.assign({}, AJAXApi.defaultOptions.errorToast, toastOptions) : toastOptions
        if (!skip) {
            if (this.options.provideFeedback) {
                UI.toast(alteredToastOptions)
            } else {
                if (isError) {
                    console.error(alteredToastOptions.body)
                }
            }
        }
    }

    mergeOptions(newOptions) {
        this.options = Object.assign({}, this.options, newOptions)
    }

    static mergeFormData(formData, dataToMerge) {
        for (const [fieldName, value] of Object.entries(dataToMerge)) {
            formData.set(fieldName, value)
        }
        return formData
    }

    static async quickFetchURL(url, options={}) {
        const constAlteredOptions = Object.assign({}, {provideFeedback: false}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.fetchURL(url, constAlteredOptions.skipRequestHooks)
    }

    static async quickFetchForm(url, options={}) {
        const constAlteredOptions = Object.assign({}, {provideFeedback: false}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.fetchForm(url, constAlteredOptions.skipRequestHooks)
    }

    static async quickPostForm(form, dataToMerge={}, options={}) {
        const constAlteredOptions = Object.assign({}, {}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.postForm(form, dataToMerge, constAlteredOptions.skipRequestHooks)
    }

    static async quickFetchAndPostForm(url, dataToMerge={}, options={}) {
        const constAlteredOptions = Object.assign({}, {}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.fetchAndPostForm(url, dataToMerge, constAlteredOptions.skipRequestHooks)
    }

    async fetchURL(url, skipRequestHooks=false, skipFeedback=false) {
        if (!skipRequestHooks) {
            this.beforeRequest()
        }
        let toReturn
        try {
            const response = await fetch(url, AJAXApi.genericRequestConfigGET);
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            const data = await response.text();
            this.provideFeedback({
                variant: 'success',
                title: 'URL fetched',
            }, false, skipFeedback);
            toReturn = data;
        } catch (error) {
            this.provideFeedback({
                variant: 'danger',
                title: 'There has been a problem with the operation',
                body: error
            }, true, skipFeedback);
            toReturn = Promise.reject(error);
        } finally {
            if (!skipRequestHooks) {
                this.afterRequest()
            }
        }
        return toReturn
    }

    async fetchForm(url, skipRequestHooks=false, skipFeedback=false) {
        if (!skipRequestHooks) {
            this.beforeRequest()
        }
        let toReturn
        try {
            const response = await fetch(url, AJAXApi.genericRequestConfigGET);
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            const formHtml = await response.text();
            let tmpNode = document.createElement("div");
            tmpNode.innerHTML = formHtml;
            let form = tmpNode.getElementsByTagName('form');
            if (form.length == 0) {
                throw new Error('The server did not return a form element')
            }
            toReturn = form[0];
        } catch (error) {
            this.provideFeedback({
                variant: 'danger',
                title: 'There has been a problem with the operation',
                body: error
            }, true, skipFeedback);
            toReturn = Promise.reject(error);
        } finally {
            if (!skipRequestHooks) {
                this.afterRequest()
            }
        }
        return toReturn
    }

    async postForm(form, dataToMerge={}, skipRequestHooks=false, skipFeedback=false) {
        if (!skipRequestHooks) {
            this.beforeRequest()
        }
        let toReturn
        let feedbackShown = false
        try {
            try {
                let formData = new FormData(form)
                formData = AJAXApi.mergeFormData(formData, dataToMerge)
                let requestConfig = AJAXApi.genericRequestConfigPOST
                if (this.options.renderedHTMLOnFailureRequested) {
                    requestConfig.headers.append(AJAXApi.renderHTMLOnFailureHeader.name, AJAXApi.renderHTMLOnFailureHeader.value)
                }
                let options = {
                    ...requestConfig,
                    body: formData,
                };
                const response = await fetch(form.action, options);
                if (!response.ok) {
                    throw new Error('Network response was not ok')
                }
                const clonedResponse = response.clone()
                try {
                    const data = await response.json()
                    if (data.success) {
                        this.provideFeedback({
                            variant: 'success',
                            body: data.message
                        }, false, skipFeedback);
                        toReturn = data;
                    } else {
                        this.provideFeedback({
                            variant: 'danger',
                            title: 'There has been a problem with the operation',
                            body: data.errors
                        }, true, skipFeedback);
                        feedbackShown = true
                        toReturn = Promise.reject(data.errors);
                    }
                } catch (error) { // could not parse JSON
                    if (this.options.renderedHTMLOnFailureRequested) {
                        const data = await clonedResponse.text();
                        toReturn = {
                            success: 0,
                            html: data,
                        }
                    }
                }
            } catch (error) {
                this.provideFeedback({
                    variant: 'danger',
                    title: 'There has been a problem with the operation',
                    body: error
                }, true, feedbackShown);
                toReturn = Promise.reject(error);
            }
        } catch (error) {
            toReturn = Promise.reject(error);
        } finally {
            if (!skipRequestHooks) {
                this.afterRequest()
            }
        }
        return toReturn
    }
    
    async fetchAndPostForm(url, dataToMerge={}, skipRequestHooks=false) {
        if (!skipRequestHooks) {
            this.beforeRequest()
        }
        let toReturn
        try {
            const form = await this.fetchForm(url, true, true);
            toReturn = await this.postForm(form, dataToMerge, true, true)
        } catch (error) {
            toReturn = Promise.reject(error);
        } finally {
            if (!skipRequestHooks) {
                this.afterRequest()
            }
        }
        return toReturn
    }

    beforeRequest() {
        if (this.options.statusNode !== false) {
            this.toggleLoading(true)
        }
    }
    
    afterRequest() {
        if (this.options.statusNode !== false) {
            this.toggleLoading(false)
        }
    }

    toggleLoading(loading) {
        if (this.loadingOverlay === false) {
            this.loadingOverlay = new OverlayFactory({node: this.options.statusNode});
        }
        if (loading) {
            this.loadingOverlay.show()
        } else {
            this.loadingOverlay.hide()
            
        }
    }
}

