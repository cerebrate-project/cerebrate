/** AJAXApi class providing helpers to perform AJAX request */
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
        name: 'X-Force-HTML-On-Validation-Failure',
        value: '1'
    }

    /**
     * @namespace
     * @property {boolean}         provideFeedback              - The ID to be used for the toast's container
     * @property {(jQuery|string)} statusNode                   - The node on which the loading overlay should be placed (OverlayFactory.node)
     * @property {boolean}         forceHTMLOnValidationFailure - If true, attach a special header to ask for HTML instead of JSON in case of form validation failure
     * @property {Object}          errorToast                   - The options supported by Toaster#defaultOptions
     */
    static defaultOptions = {
        provideFeedback: true,
        statusNode: false,
        forceHTMLOnValidationFailure: false,
        errorToast: {
            delay: 10000
        }
    }
    options = {}
    loadingOverlay = false

    /**
     * Instantiate an AJAXApi object.
     * @param  {Object} options - The options supported by AJAXApi#defaultOptions
     */
    constructor(options) {
        this.mergeOptions(AJAXApi.defaultOptions)
        this.mergeOptions(options)
    }

    /**
     * Based on the current configuration, provide feedback to the user via toast, console or do not
     * @param {Object} toastOptions - The options supported by Toaster#defaultOptions
     * @param {boolean} isError     - If true and toast feedback is disable, write the feedback in the console
     * @param {boolean} skip        - If true, skip the feedback regardless of the configuration
     */
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

    /**
     * Merge newOptions configuration into the current object
     * @param {Object} The options supported by AJAXApi#defaultOptions
     */
    mergeOptions(newOptions) {
        this.options = Object.assign({}, this.options, newOptions)
    }

    /**
     * 
     * @param  {FormData} formData       - The data of a form
     * @param  {Object}   dataToMerge    - Data to be merge into formData
     * @return {FormData} The form data merged with the additional dataToMerge data
     */
    static mergeFormData(formData, dataToMerge) {
        for (const [fieldName, value] of Object.entries(dataToMerge)) {
            formData.set(fieldName, value)
        }
        return formData
    }

    /**
     * @param {string} url      - The URL to fetch
     * @param {Object} [options={}]  - The options supported by AJAXApi#defaultOptions 
     * @return {Promise<string>} Promise object resolving to the fetched HTML
     */
    static async quickFetchURL(url, options={}) {
        const constAlteredOptions = Object.assign({}, {provideFeedback: false}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.fetchURL(url, constAlteredOptions.skipRequestHooks)
    }

    /**
     * @param {string} url          - The URL to fetch
     * @param {Object} [options={}] - The options supported by AJAXApi#defaultOptions 
     * @return {Promise<HTMLFormElement>} Promise object resolving to the fetched form
     */
    static async quickFetchForm(url, options={}) {
        const constAlteredOptions = Object.assign({}, {provideFeedback: false}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.fetchForm(url, constAlteredOptions.skipRequestHooks)
    }

    /**
     * @param {HTMLFormElement} form    - The form to be posted
     * @param {Object} [dataToMerge={}] - Additional data to be integrated or modified in the form
     * @param {Object} [options={}]     - The options supported by AJAXApi#defaultOptions 
     * @return {Promise<Object>} Promise object resolving to the result of the POST operation
     */
    static async quickPostForm(form, dataToMerge={}, options={}) {
        const constAlteredOptions = Object.assign({}, {}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.postForm(form, dataToMerge, constAlteredOptions.skipRequestHooks)
    }

    /**
     * @param {string} url              - The URL from which to fetch the form
     * @param {Object} [dataToMerge={}] - Additional data to be integrated or modified in the form
     * @return {Promise<Object>} Promise object resolving to the result of the POST operation
     */
    static async quickFetchAndPostForm(url, dataToMerge={}, options={}) {
        const constAlteredOptions = Object.assign({}, {}, options)
        const tmpApi = new AJAXApi(constAlteredOptions)
        return tmpApi.fetchAndPostForm(url, dataToMerge, constAlteredOptions.skipRequestHooks)
    }

    /**
     * @param {string}  url                      - The URL to fetch
     * @param {boolean} [skipRequestHooks=false] - If true, default request hooks will be skipped
     * @param {boolean} [skipFeedback=false]     - Pass this value to the AJAXApi.provideFeedback function
     * @return {Promise<string>} Promise object resolving to the fetched HTML
     */
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
            const dataHtml = await response.text();
            this.provideFeedback({
                variant: 'success',
                title: 'URL fetched',
            }, false, skipFeedback);
            toReturn = dataHtml;
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

    /**
     * @param {string}  url                      - The URL to fetch
     * @param {boolean} [skipRequestHooks=false] - If true, default request hooks will be skipped
     * @param {boolean} [skipFeedback=false]     - Pass this value to the AJAXApi.provideFeedback function
     * @return {Promise<HTMLFormElement>} Promise object resolving to the fetched HTML
     */
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

     /**
     * @param {HTMLFormElement}  form                     - The form to be posted
     * @param {Object} [dataToMerge={}]          - Additional data to be integrated or modified in the form
     * @param {boolean} [skipRequestHooks=false] - If true, default request hooks will be skipped
     * @param {boolean} [skipFeedback=false]     - Pass this value to the AJAXApi.provideFeedback function
     * @return {Promise<Object>} Promise object resolving to the result of the POST operation
     */
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
                if (this.options.forceHTMLOnValidationFailure) {
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
                    if (this.options.forceHTMLOnValidationFailure) {
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
    
    /**
     * @param {string} url                       - The URL from which to fetch the form
     * @param {Object} [dataToMerge={}]          - Additional data to be integrated or modified in the form
     * @param {boolean} [skipRequestHooks=false] - If true, default request hooks will be skipped
     * @return {Promise<Object>} Promise object resolving to the result of the POST operation
     */
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

    /** Based on the configuration, show the loading overlay */
    beforeRequest() {
        if (this.options.statusNode !== false) {
            this.toggleLoading(true)
        }
    }

    /** Based on the configuration, hide the loading overlay */
    afterRequest() {
        if (this.options.statusNode !== false) {
            this.toggleLoading(false)
        }
    }

    /** Show or hide the loading overlay */
    toggleLoading(loading) {
        if (this.loadingOverlay === false) {
            this.loadingOverlay = new OverlayFactory(this.options.statusNode);
        }
        if (loading) {
            this.loadingOverlay.show()
        } else {
            this.loadingOverlay.hide()
            
        }
    }
}

