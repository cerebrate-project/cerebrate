class AJAXApi {
    static genericRequestHeaders = new Headers({
        'X-Requested-With': 'XMLHttpRequest'
    });
    static genericRequestConfigGET = {
        headers: AJAXApi.genericRequestHeaders
    }
    static genericRequestConfigPOST = {
        headers: AJAXApi.genericRequestHeaders,
        redirect: 'manual',
        method: 'POST',
    }

    static defaultOptions = {
        showToast: true,
        statusNode: false
    }
    options = {}
    loadingOverlay = false

    constructor(options) {
        this.mergeOptions(AJAXApi.defaultOptions)
        this.mergeOptions(options)
    }

    provideFeedback(options) {
        if (this.options.showToast) {
            UI.toast(options)
        } else {
            console.error(options.body)
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

    async fetchURL(url, skipRequestHooks=false) {
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
            toReturn = data;
        } catch (error) {
            this.provideFeedback({
                variant: 'danger',
                title: 'There has been a problem with the operation',
                body: error
            });
            toReturn = Promise.reject(error);
        } finally {
            if (!skipRequestHooks) {
                this.afterRequest()
            }
        }
        return toReturn
    }

    async fetchForm(url, skipRequestHooks=false) {
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
            });
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
            const form = await this.fetchForm(url, true);
            try {
                let formData = new FormData(form)
                formData = AJAXApi.mergeFormData(formData, dataToMerge)
                let options = {
                    ...AJAXApi.genericRequestConfigPOST,
                    body: formData,
                };
                const response = await fetch(form.action, options);
                if (!response.ok) {
                    throw new Error('Network response was not ok')
                }
                const data = await response.json();
                if (data.success) {
                    this.provideFeedback({
                        variant: 'success',
                        body: data.message
                    });
                    toReturn = data;
                } else {
                    this.provideFeedback({
                        variant: 'danger',
                        title: 'There has been a problem with the operation',
                        body: data.errors
                    });
                    toReturn = Promise.reject(error);
                }
            } catch (error) {
                this.provideFeedback({
                    variant: 'danger',
                    title: 'There has been a problem with the operation',
                    body: error
                });
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

