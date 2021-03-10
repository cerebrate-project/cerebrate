/** Class containing common UI functionalities */
class UIFactory {
    /**
     * Create and display a toast
     * @param  {Object} options - The options to be passed to the Toaster class
     * @return {Object} The Toaster object
     */
    toast(options) {
        const theToast = new Toaster(options);
        theToast.makeToast()
        theToast.show()
        return theToast
    }

    /**
     * Create and display a modal
     * @param  {Object} options - The options to be passed to the ModalFactory class
     * @return {Object} The ModalFactory object
     */
    modal(options) {
        const theModal = new ModalFactory(options);
        theModal.makeModal()
        theModal.show()
        return theModal
    }

    /**
     * Create and display a modal where the modal's content is fetched from the provided URL.
     * @param  {string} url - The URL from which the modal's content should be fetched
     * @param  {ModalFactory~POSTSuccessCallback} POSTSuccessCallback - The callback that handles successful form submission
     * @param  {ModalFactory~POSTFailCallback} POSTFailCallback - The callback that handles form submissions errors and validation errors.
     * @return {Promise<Object>} Promise object resolving to the ModalFactory object
     */
    modalFromURL(url, POSTSuccessCallback, POSTFailCallback) {
        return AJAXApi.quickFetchURL(url).then((modalHTML) => {
            const theModal = new ModalFactory({
                rawHtml: modalHTML,
                POSTSuccessCallback: POSTSuccessCallback !== undefined ? POSTSuccessCallback : () => {},
                POSTFailCallback: POSTFailCallback !== undefined ? POSTFailCallback : (errorMessage) => {},
            });
            theModal.makeModal(modalHTML)
            theModal.show()
            theModal.$modal.data('modalObject', theModal)
            return theModal
        })
    }

    /**
     * Creates and displays a modal where the modal's content is fetched from the provided URL. Reloads the table after a successful operation and handles displayOnSuccess option
     * @param  {string} url - The URL from which the modal's content should be fetched
     * @param  {string} reloadUrl - The URL from which the data should be fetched after confirming
     * @param  {string} tableId - The table ID which should be reloaded on success
     * @return {Promise<Object>} Promise object resolving to the ModalFactory object
     */
    openModalFromURL(url, reloadUrl=false, tableId=false) {
        return UI.modalFromURL(url, (data) => {
            let reloaded = false
            if (reloadUrl === false || tableId === false) { // Try to get information from the DOM
                let $elligibleTable = $('table.table')
                let currentModel = location.pathname.split('/')[1]
                if ($elligibleTable.length == 1 && currentModel.length > 0) {
                    let $container = $elligibleTable.closest('div[id^="table-container-"]')
                    if ($container.length == 1) {
                        UI.reload(`/${currentModel}/index`, $container, $elligibleTable)
                        reloaded = true
                    } else {
                        $container = $elligibleTable.closest('div[id^="single-view-table-container-"]')
                        if ($container.length == 1) {
                            UI.reload(location.pathname, $container, $elligibleTable)
                            reloaded = true
                        }
                    }
                }
            } else {
                UI.reload(reloadUrl, $(`#table-container-${tableId}`), $(`#table-container-${tableId} table.table`))
                reloaded = true
            }
            if (data.additionalData !== undefined && data.additionalData.displayOnSuccess !== undefined) {
                UI.modal({
                    rawHtml: data.additionalData.displayOnSuccess
                })
            } else {
                if (!reloaded) {
                    location.reload()
                }
            }
        })
    }

    /**
     * Fetch HTML from the provided URL and override the $container's content. $statusNode allows to specify another HTML node to display the loading
     * @param  {string} url - The URL from which the $container's content should be fetched
     * @param  {(jQuery|string)} $container - The container that should hold the data fetched
     * @param  {(jQuery|string)} [$statusNode=null] - A reference to a HTML node on which the loading animation should be displayed. If not provided, $container will be used
     * @param  {array} [additionalStatusNodes=[]] - A list of other node on which to apply overlay. Must contain the node and possibly the overlay configuration
     * @return {Promise<jQuery>} Promise object resolving to the $container object after its content has been replaced
     */
    reload(url, $container, $statusNode=null, additionalStatusNodes=[]) {
        $container = $($container)
        $statusNode = $($statusNode)
        if (!$statusNode) {
            $statusNode = $container
        }
        const otherStatusNodes = []
        additionalStatusNodes.forEach(otherStatusNode => {
            const loadingOverlay = new OverlayFactory(otherStatusNode.node, otherStatusNode.config)
            loadingOverlay.show()
            otherStatusNodes.push(loadingOverlay)
        })
        return AJAXApi.quickFetchURL(url, {
            statusNode: $statusNode[0],
        }).then((theHTML) => {
            $container.replaceWith(theHTML)
            return $container
        }).finally(() => {
            otherStatusNodes.forEach(overlay => {
                overlay.hide()
            })
        })
    }

    /**
     * Place an overlay onto a node and remove it whenever the promise resolves
     * @param {(jQuery|string)} node       - The node on which the overlay should be placed
     * @param {Promise} promise            - A promise to be fulfilled
     * @param {Object} [overlayOptions={}  - The options to be passed to the overlay class
     * @return {Promise} Result of the passed promised
     */
    overlayUntilResolve(node, promise, overlayOptions={}) {
        const $node = $(node)
        const loadingOverlay = new OverlayFactory($node[0], overlayOptions);
        loadingOverlay.show()
        promise.finally(() => {
            loadingOverlay.hide()
        })
        return promise
    }
}

/** Class representing a Toast */
class Toaster {
    /**
     * Create a Toast.
     * @param  {Object} options - The options supported by Toaster#defaultOptions
     */
    constructor(options) {
        this.options = Object.assign({}, Toaster.defaultOptions, options)
        this.bsToastOptions = {
            autohide: this.options.autohide,
            delay: this.options.delay,
        }
    }

    /**
     * @namespace
     * @property {number}  id           - The ID to be used for the toast's container
     * @property {string}  title        - The title's content of the toast
     * @property {string}  muted        - The muted's content of the toast
     * @property {string}  body         - The body's content of the toast
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} variant - The variant of the toast
     * @property {boolean} autohide    - If the toast show be hidden after some time defined by the delay
     * @property {number}  delay        - The number of milliseconds the toast should stay visible before being hidden
     * @property {string}  titleHtml    - The raw HTML title's content of the toast
     * @property {string}  mutedHtml    - The raw HTML muted's content of the toast
     * @property {string}  bodyHtml     - The raw HTML body's content of the toast
     * @property {boolean} closeButton - If the toast's title should include a close button
     */
    static defaultOptions = {
        id: false,
        title: false,
        muted: false,
        body: false,
        variant: 'default',
        autohide: true,
        delay: 5000,
        titleHtml: false,
        mutedHtml: false,
        bodyHtml: false,
        closeButton: true,
    }

    /** Create the HTML of the toast and inject it into the DOM */
    makeToast() {
        if (this.isValid()) {
            this.$toast = Toaster.buildToast(this.options)
            $('#mainToastContainer').append(this.$toast)
        }
    }

    /** Display the toast to the user and remove it from the DOM once it get hidden */
    show() {
        if (this.isValid()) {
            var that = this
            this.$toast.toast(this.bsToastOptions)
                .toast('show')
                .on('hidden.bs.toast', function () {
                    that.removeToast()
                })
        }
    }

    /** Remove the toast from the DOM */
    removeToast() {
        this.$toast.remove();
    }

    /**
     * Check wheter a toast is valid
     * @return {boolean} Return true if the toast contains at least data to be rendered
     */
    isValid() {
        return this.options.title !== false || this.options.titleHtml !== false ||
        this.options.muted !== false || this.options.mutedHtml !== false ||
        this.options.body !== false || this.options.bodyHtml !== false
    }

    /**
     * Build the toast HTML
     * @param {Object} options - The options supported by Toaster#defaultOptions to build the toast
     * @return {jQuery} The toast jQuery object
     */
    static buildToast(options) {
        var $toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true"/>')
        if (options.id !== false) {
            $toast.attr('id', options.id)
        }
        $toast.addClass('toast-' + options.variant)
        if (options.title !== false || options.titleHtml !== false || options.muted !== false || options.mutedHtml !== false) {
            var $toastHeader = $('<div class="toast-header"/>')
            $toastHeader.addClass('toast-' + options.variant)
            if (options.title !== false || options.titleHtml !== false) {
                var $toastHeaderText
                if (options.titleHtml !== false) {
                    $toastHeaderText = $('<div class="mr-auto"/>').html(options.titleHtml);
                } else {
                    $toastHeaderText = $('<strong class="mr-auto"/>').text(options.title)
                }
                $toastHeader.append($toastHeaderText)
            }
            if (options.muted !== false || options.mutedHtml !== false) {
                var $toastHeaderMuted
                if (options.mutedHtml !== false) {
                    $toastHeaderMuted = $('<div/>').html(options.mutedHtml)
                } else {
                    $toastHeaderMuted = $('<small class="text-muted"/>').text(options.muted)
                }
                $toastHeader.append($toastHeaderMuted)
            }
            if (options.closeButton) {
                var $closeButton = $('<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close"><span aria-hidden="true">&times;</span></button>')
                $toastHeader.append($closeButton)
            }
            $toast.append($toastHeader)
        }
        if (options.body !== false || options.bodyHtml !== false) {
            var $toastBody
            if (options.bodyHtml !== false) {
                $toastBody = $('<div class="toast-body"/>').html(options.mutedHtml)
            } else {
                $toastBody = $('<div class="toast-body"/>').text(options.body)
            }
            $toast.append($toastBody)
        }
        return $toast
    }
}

/** Class representing a Modal */
class ModalFactory {
    /**
     * Create a Modal.
     * @param  {Object} options - The options supported by ModalFactory#defaultOptions
     */
    constructor(options) {
        this.options = Object.assign({}, ModalFactory.defaultOptions, options)
        if (this.options.rawHtml && options.POSTSuccessCallback !== undefined) {
            this.attachSubmitButtonListener = true
        }
        if (options.type === undefined && options.cancel !== undefined) {
            this.options.type = 'confirm'
        }
        this.bsModalOptions = {
            show: true
        }
        if (this.options.backdropStatic) {
            this.bsModalOptions['backdrop'] = 'static'
        }
    }

    /**
     * @callback ModalFactory~closeModalFunction
     */
    /**
     * @callback ModalFactory~confirm
     * @param {ModalFactory~closeModalFunction} closeFunction - A function that will close the modal if called
     * @param {Object} modalFactory - The instance of the ModalFactory
     * @param {Object} evt - The event that triggered the confirm operation
     */
    /**
     * @callback ModalFactory~cancel
     * @param {ModalFactory~closeModalFunction} closeFunction - A function that will close the modal if called
     * @param {Object} modalFactory - The instance of the ModalFactory
     * @param {Object} evt - The event that triggered the confirm operation
     */
    /**
     * @callback ModalFactory~APIConfirm
     * @param {AJAXApi} ajaxApi - An instance of the AJAXApi with the AJAXApi.statusNode linked to the modal confirm button
     */
    /**
     * @callback ModalFactory~APIError
     * @param {ModalFactory~closeModalFunction} closeFunction - A function that will close the modal if called
     * @param {Object} modalFactory - The instance of the ModalFactory
     * @param {Object} evt - The event that triggered the confirm operation
     */
    /**
     * @callback ModalFactory~shownCallback
     * @param {Object} modalFactory - The instance of the ModalFactory
     */
    /**
     * @callback ModalFactory~hiddenCallback
     * @param {Object} modalFactory - The instance of the ModalFactory
     */
    /**
     * @callback ModalFactory~POSTSuccessCallback
     * @param {Object} data - The data received from the successful POST operation
     */
    /**
     * @callback ModalFactory~POSTFailCallback
     * @param {string} errorMessage
     */
    /**
     * @namespace
     * @property {number} id                               - The ID to be used for the modal's container
     * @property {string=('sm'|'lg'|'xl'|'')} size         - The size of the modal
     * @property {boolean} centered                        - Should the modal be vertically centered
     * @property {boolean} scrollable                      - Should the modal be scrollable
     * @property {boolean} backdropStatic                  - When set, the modal will not close when clicking outside it.
     * @property {string} title                            - The title's content of the modal
     * @property {string} titleHtml                        - The raw HTML title's content of the modal
     * @property {string} body                             - The body's content of the modal
     * @property {string} bodyHtml                         - The raw HTML body's content of the modal
     * @property {string} rawHtml                          - The raw HTML of the whole modal. If provided, will override any other content
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} variant - The variant of the modal
     * @property {string} modalClass                       - Classes to be added to the modal's container
     * @property {string} headerClass                      - Classes to be added to the modal's header
     * @property {string} bodyClass                        - Classes to be added to the modal's body
     * @property {string} footerClass                      - Classes to be added to the modal's footer
     * @property {string=('ok-only','confirm','confirm-success','confirm-warning','confirm-danger')} type - Pre-configured template covering most use cases
     * @property {string} confirmText                      - The text to be placed in the confirm button
     * @property {string} cancelText                       - The text to be placed in the cancel button
     * @property {boolean} closeManually                   - If true, the modal will be closed automatically whenever a footer's button is pressed
     * @property {boolean} closeOnSuccess                  - If true, the modal will be closed if the $FILL_ME operation is successful
     * @property {ModalFactory~confirm} confirm                         - The callback that should be called if the user confirm the modal
     * @property {ModalFactory~cancel} cancel                           - The callback that should be called if the user cancel the modal
     * @property {ModalFactory~APIConfirm} APIConfirm                   - The callback that should be called if the user confirm the modal. Behaves like the confirm option but provides an AJAXApi object that can be used to issue requests
     * @property {ModalFactory~APIError} APIError                       - The callback called if the APIConfirm callback fails.
     * @property {ModalFactory~shownCallback} shownCallback             - The callback that should be called whenever the modal is shown
     * @property {ModalFactory~hiddenCallback} hiddenCallback           - The callback that should be called whenever the modal is hiddenAPIConfirm
     * @property {ModalFactory~POSTSuccessCallback} POSTSuccessCallback - The callback that should be called if the POST operation has been a success
     * @property {ModalFactory~POSTFailCallback} POSTFailCallback       - The callback that should be called if the POST operation has been a failure (Either the request failed or the form validation did not pass)
     */
    static defaultOptions = {
        id: false,
        size: 'md',
        centered: false,
        scrollable: false,
        backdropStatic: false,
        title: '',
        titleHtml: false,
        body: false,
        bodyHtml: false,
        rawHtml: false,
        variant: '',
        modalClass: '',
        headerClass: '',
        bodyClass: '',
        footerClass: '',
        type: 'ok-only',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        closeManually: false,
        closeOnSuccess: true,
        confirm: function() {},
        cancel: function() {},
        APIConfirm: null,
        APIError: function() {},
        shownCallback: function() {},
        hiddenCallback: function() {},
        POSTSuccessCallback: function() {},
        POSTFailCallback: function() {},
    }

    static availableType = [
        'ok-only',
        'confirm',
        'confirm-success',
        'confirm-warning',
        'confirm-danger',
    ]

    static closeButtonHtml = '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'

    /** Create the HTML of the modal and inject it into the DOM */
    makeModal() {
        if (this.isValid()) {
            this.$modal = this.buildModal()
            $('#mainModalContainer').append(this.$modal)
        } else {
            console.log('Modal not valid')
        }
    }

    /** Display the modal and remove it form the DOM once it gets hidden */
    show() {
        if (this.isValid()) {
            var that = this
            this.$modal.modal(this.bsModalOptions)
                .on('hidden.bs.modal', function () {
                    that.removeModal()
                    that.options.hiddenCallback(that)
                })
                .on('shown.bs.modal', function () {
                    that.options.shownCallback(that)
                    if (that.attachSubmitButtonListener) {
                        that.findSubmitButtonAndAddListener()
                    }
                })
        } else {
            console.log('Modal not valid')
        }
    }

    /** Hide the modal using the bootstrap modal's hide command */
    hide() {
        this.$modal.modal('hide')
    }
    
    /** Remove the modal from the DOM */
    removeModal() {
        this.$modal.remove();
    }

    /**
     * Check wheter a modal is valid
     * @return {boolean} Return true if the modal contains at least data to be rendered
     */
    isValid() {
        return this.options.title !== false || this.options.titleHtml !== false ||
        this.options.body !== false ||  this.options.bodyHtml !== false ||
        this.options.rawHtml !== false
    }

    /**
     * Build the modal HTML
     * @return {jQuery} The modal jQuery object
     */
    buildModal() {
        const $modal = $('<div class="modal fade" tabindex="-1" aria-hidden="true"/>')
        if (this.options.id !== false) {
            $modal.attr('id', this.options.id)
            $modal.attr('aria-labelledby', this.options.id)
        }
        if (this.options.modalClass) {
            $modal.addClass(this.options.modalClass)
        }
        let $modalDialog
        if (this.options.rawHtml) {
            $modalDialog = $(this.options.rawHtml)
            if ($modalDialog.data('backdrop') == 'static') {
                this.bsModalOptions['backdrop'] = 'static'
            }
        } else {
            $modalDialog = $('<div class="modal-dialog"/>')
            const $modalContent = $('<div class="modal-content"/>')
            if (this.options.title !== false || this.options.titleHtml !== false) {
                const $modalHeader = $('<div class="modal-header"/>')
                if (this.options.headerClass) {
                    $modalHeader.addClass(this.options.headerClass)
                }
                let $modalHeaderText
                if (this.options.titleHtml !== false) {
                    $modalHeaderText = $('<div/>').html(this.options.titleHtml);
                } else {
                    $modalHeaderText = $('<h5 class="modal-title"/>').text(this.options.title)
                }
                $modalHeader.append($modalHeaderText, ModalFactory.getCloseButton())
                $modalContent.append($modalHeader)
            }
    
            if (this.options.body !== false || this.options.bodyHtml !== false) {
                const $modalBody = $('<div class="modal-body"/>')
                if (this.options.bodyClass) {
                    $modalBody.addClass(this.options.bodyClass)
                }
                let $modalBodyText
                if (this.options.bodyHtml !== false) {
                    $modalBodyText = $('<div/>').html(this.options.bodyHtml);
                } else {
                    $modalBodyText = $('<div/>').text(this.options.body)
                }
                $modalBody.append($modalBodyText)
                $modalContent.append($modalBody)
            }
    
            const $modalFooter = $('<div class="modal-footer"/>')
            if (this.options.footerClass) {
                $modalFooter.addClass(this.options.footerClass)
            }
            $modalFooter.append(this.getFooterBasedOnType())
            $modalContent.append($modalFooter)
    
            $modalDialog.append($modalContent)
        }
        $modal.append($modalDialog)
        return $modal
    }

    /** Returns the correct footer data based on the provided type */
    getFooterBasedOnType() {
        if (this.options.type == 'ok-only') {
            return this.getFooterOkOnly()
        } else if (this.options.type.includes('confirm')) {
            return this.getFooterConfirm()
        } else {
            return this.getFooterOkOnly()
        }
    }

    /** Generate the ok-only footer type */
    getFooterOkOnly() {
        return [
            $('<button type="button" class="btn btn-primary">OK</button>')
                .attr('data-dismiss', 'modal'),
        ]
    }

    /** Generate the confirm-* footer type */
    getFooterConfirm() {
        let variant = this.options.type.split('-')[1]
        variant = variant !== undefined ? variant : 'primary'
        const $buttonCancel = $('<button type="button" class="btn btn-secondary" data-dismiss="modal"></button>')
                .text(this.options.cancelText)
                .click(
                    (evt) => {
                        this.options.cancel(() => { this.hide() }, this, evt)
                    }
                )
                .attr('data-dismiss', (this.options.closeManually || !this.options.closeOnSuccess) ? '' : 'modal')

        const $buttonConfirm = $('<button type="button" class="btn"></button>')
                .addClass('btn-' + variant)
                .text(this.options.confirmText)
                .click(this.getConfirmationHandlerFunction())
                .attr('data-dismiss', (this.options.closeManually || this.options.closeOnSuccess) ? '' : 'modal')
        return [$buttonCancel, $buttonConfirm]
    }

    /** Return a close button */
    static getCloseButton() {
        return $(ModalFactory.closeButtonHtml)
    }

    /** Generate the function that will be called when the user confirm the modal */
    getConfirmationHandlerFunction() {
        return (evt) => {
            let confirmFunction = this.options.confirm
            if (this.options.APIConfirm) {
                const tmpApi = new AJAXApi({
                    statusNode: evt.target
                })
                confirmFunction = () => { return this.options.APIConfirm(tmpApi) }
            }
            let confirmResult = confirmFunction(() => { this.hide() }, this, evt)
            if (confirmResult === undefined) {
                this.hide()
            } else {
                confirmResult.then((data) => {
                    if (this.options.closeOnSuccess) {
                        this.hide()
                    }
                })
                .catch((err) => {
                    this.options.APIError(() => { this.hide() }, this, evt)
                })
            }
        }
    }

    /** Attach the submission click listener for modals that have been generated by raw HTML */
    findSubmitButtonAndAddListener(clearOnclick=true) {
        const $submitButton = this.$modal.find('.modal-footer #submitButton')
        if ($submitButton[0]) {
            const formID = $submitButton.data('form-id')
            let $form
            if (formID) {
                $form = $(formID)
            } else {
                $form = this.$modal.find('form')
            }
            if (clearOnclick) {
                $submitButton[0].removeAttribute('onclick')
            }
    
            this.options.APIConfirm = (tmpApi) => {
                return tmpApi.postForm($form[0])
                    .then((data) => {
                        if (data.success) {
                            this.options.POSTSuccessCallback(data)
                        } else { // Validation error
                            this.injectFormValidationFeedback(form, data.errors)
                            return Promise.reject('Validation error');
                        }
                    })
                    .catch((errorMessage) => {
                        this.options.POSTFailCallback(errorMessage)
                        return Promise.reject(errorMessage);
                    })
            }
            $submitButton.click(this.getConfirmationHandlerFunction())
        }
    }
}

/** Class representing an loading overlay */
class OverlayFactory {
    /**
     * Create a loading overlay
     * @param {(jQuery|string|HTMLButtonElement)} node    - The node on which the overlay should be placed
     * @param {Object}                            options - The options supported by OverlayFactory#defaultOptions 
     */
    constructor(node, options={}) {
        this.node = node
        this.$node = $(this.node)
        if (darkMode) {
            this.options = Object.assign({}, OverlayFactory.defaultOptionsDarkTheme, options)
        } else {
            this.options = Object.assign({}, OverlayFactory.defaultOptions, options)
        }
        this.options.auto = options.auto ? this.options.auto : !(options.variant || options.spinnerVariant)
        if (this.options.auto) {
            this.adjustOptionsBasedOnNode()
        }
    }

    /**
     * @namespace
     * @property {string}  text - A small text indicating the reason of the overlay
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} variant - The variant of the overlay
     * @property {number}  opacity        - The opacity of the overlay
     * @property {boolean} rounded        - If the overlay should be rounded
     * @property {number}  auto           - Whether overlay and spinner options should be adapted automatically based on the node
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} spinnerVariant - The variant of the spinner
     * @property {boolean} spinnerSmall   - If the spinner inside the overlay should be small
     * @property {string=('border'|'grow')} spinnerSmall   - If the spinner inside the overlay should be small
     */
    static defaultOptionsDarkTheme = {
        text: '',
        variant: 'light',
        opacity: 0.25,
        blur: '2px',
        rounded: false,
        auto: true,
        spinnerVariant: '',
        spinnerSmall: false,
        spinnerType: 'border',
        fallbackBoostrapVariant: 'light'
    }
    static defaultOptions = {
        text: '',
        variant: 'light',
        opacity: 0.85,
        blur: '2px',
        rounded: false,
        auto: true,
        spinnerVariant: '',
        spinnerSmall: false,
        spinnerType: 'border',
        fallbackBoostrapVariant: ''
    }

    static overlayWrapper = '<div aria-busy="true" class="position-relative"/>'
    static overlayContainer = '<div class="position-absolute text-nowrap" style="inset: 0px; z-index: 10;"/>'
    static overlayBg = '<div class="position-absolute" style="inset: 0px;"/>'
    static overlaySpinner = '<div class="position-absolute" style="top: 50%; left: 50%; transform: translateX(-50%) translateY(-50%);"><span aria-hidden="true" class=""><!----></span></div></div>'
    static overlayText = '<span class="ml-1 align-text-top"></span>'

    shown = false
    originalNodeIndex = 0

     /** Create the HTML of the overlay */
    buildOverlay() {
        this.$overlayWrapper = $(OverlayFactory.overlayWrapper)
        this.$overlayContainer = $(OverlayFactory.overlayContainer)
        this.$overlayBg = $(OverlayFactory.overlayBg)
            .addClass([`bg-${this.options.variant}`, (this.options.rounded ? 'rounded' : '')])
            .css('opacity', this.options.opacity)
        this.$overlaySpinner = $(OverlayFactory.overlaySpinner)
        this.$overlaySpinner.children().addClass(`spinner-${this.options.spinnerType}`)
        if (this.options.spinnerSmall) {
            this.$overlaySpinner.children().addClass(`spinner-${this.options.spinnerType}-sm`)
        }
        if (this.options.spinnerVariant.length > 0) {
            this.$overlaySpinner.children().addClass(`text-${this.options.spinnerVariant}`)
        }
        if (this.options.text.length > 0) {
            this.$overlayText = $(OverlayFactory.overlayText);
            this.$overlayText.addClass(`text-${this.options.spinnerVariant}`)
                .text(this.options.text)
            this.$overlaySpinner.append(this.$overlayText)
        }
    }

    /** Create the overlay, attach it to the DOM and display it */
    show() {
        this.buildOverlay()
        this.mountOverlay()
        this.shown = true
    }

    /** Hide the overlay and remove it from the DOM */
    hide() {
        if (this.shown) {
            this.unmountOverlay()
        }
        this.shown = false
    }

    /** Attach the overlay to the DOM */
    mountOverlay() {
        this.originalNodeIndex = this.$node.index()
        this.$overlayBg.appendTo(this.$overlayContainer)
        this.$overlaySpinner.appendTo(this.$overlayContainer)
        this.appendToIndex(this.$overlayWrapper, this.$node.parent(), this.originalNodeIndex)
        this.$overlayContainer.appendTo(this.$overlayWrapper)
        this.$node.prependTo(this.$overlayWrapper)
    }

    /** Remove the overlay from the DOM */
    unmountOverlay() {
        this.appendToIndex(this.$node, this.$overlayWrapper.parent(), this.originalNodeIndex)
        this.$overlayWrapper.remove()
        this.originalNodeIndex = 0
    }

    /** Append a node to the provided DOM index */
    appendToIndex($node, $targetContainer, index) {
        const $target = $targetContainer.children().eq(index);
        $node.insertBefore($target);
    }

    /** Adjust instance's options based on the provided node */
    adjustOptionsBasedOnNode() {
        if (this.$node.width() < 50 || this.$node.height() < 50) {
            this.options.spinnerSmall = true
        }
        if (this.$node.is('input[type="checkbox"]') || this.$node.css('border-radius') !== '0px') {
            this.options.rounded = true
        } 
        let classes = this.$node.attr('class')
        if (classes !== undefined) {
            classes = classes.split(' ')
            const detectedVariant = OverlayFactory.detectedBootstrapVariant(classes, this.options.fallbackBoostrapVariant)
            this.options.spinnerVariant = detectedVariant
        }
    }

    /**
     * Detect the bootstrap variant from a list of classes
     * @param {Array} classes - A list of classes containg a bootstrap variant 
     */
    static detectedBootstrapVariant(classes, fallback=OverlayFactory.defaultOptions.fallbackBoostrapVariant) {
        const re = /^[a-zA-Z]+-(?<variant>primary|success|danger|warning|info|light|dark|white|transparent)$/;
        let result
        for (let i=0; i<classes.length; i++) {
            let theClass = classes[i]
            if ((result = re.exec(theClass)) !== null) {
                if (result.groups !== undefined && result.groups.variant !== undefined) {
                    return result.groups.variant
                }
            }
        }
        return fallback
    }
}

/** Class representing a FormValidationHelper */
class FormValidationHelper {
    /**
     * Create a FormValidationHelper.
     * @param  {Object} options - The options supported by FormValidationHelper#defaultOptions
     */
    constructor(form, options={}) {
        this.form = form
        this.options = Object.assign({}, Toaster.defaultOptions, options)
    }

    /**
     * @namespace
     */
    static defaultOptions = {
    }

    /**
     * Create node containing validation information from validationError. If no field can be associated to the error, it will be placed on top
     * @param  {Object} validationErrors - The validation errors to be displayed. Keys are the fieldName that had errors, values are the error text
     */
    injectValidationErrors(validationErrors) {
        this.cleanValidationErrors()
        for (const [fieldName, errors] of Object.entries(validationErrors)) {
            this.injectValidationErrorInForm(fieldName, errors)
        }
    }

    injectValidationErrorInForm(fieldName, errors) {
        const inputField = Array.from(this.form).find(node => { return node.name == fieldName })
        if (inputField !== undefined) {
            const $messageNode = this.buildValidationMessageNode(errors)
            const $inputField = $(inputField)
            $inputField.addClass('is-invalid')
            $messageNode.insertAfter($inputField)
        } else {
            const $messageNode = this.buildValidationMessageNode(errors, true)
            const $flashContainer = $(this.form).parent().find('#flashContainer')
            $messageNode.insertAfter($flashContainer)
        }
    }

    buildValidationMessageNode(errors, isAlert=false) {
        const $messageNode = $('<div></div>')
        if (isAlert) {
            $messageNode.addClass('alert alert-danger').attr('role', 'alert')
        } else {
            $messageNode.addClass('invalid-feedback')
        }
        const hasMultipleErrors = Object.keys(errors).length > 1
        for (const [ruleName, error] of Object.entries(errors)) {
            if (hasMultipleErrors) {
                $messageNode.append($('<li></li>').text(error))
            } else {
                $messageNode.text(error)
            }
        }
        return $messageNode
    }

    cleanValidationErrors() {
        $(this.form).find('textarea, input, select').removeClass('is-invalid')
        $(this.form).find('.invalid-feedback').remove()
        $(this.form).parent().find('.alert').remove()
    }

}

class HtmlHelper {
    static table(head=[], body=[], options={}) {
        const $table = $('<table/>')
        const $thead = $('<thead/>')
        const $tbody = $('<tbody/>')
        
        $table.addClass('table')
        if (options.striped) {
            $table.addClass('table-striped')
        }
        if (options.bordered) {
            $table.addClass('table-bordered')
        }
        if (options.borderless) {
            $table.addClass('table-borderless')
        }
        if (options.hoverable) {
            $table.addClass('table-hover')
        }
        if (options.small) {
            $table.addClass('table-sm')
        }
        if (options.variant) {
            $table.addClass(`table-${options.variant}`)
        }
        if (options.tableClass) {
            $table.addClass(options.tableClass)
        }

        const $caption = $('<caption/>')
        if (options.caption) {
            if (options.caption instanceof jQuery) {
                $caption = options.caption
            } else {
                $caption.text(options.caption)
            }
        }

        const $theadRow = $('<tr/>')
        head.forEach(head => {
            if (head instanceof jQuery) {
                $theadRow.append($('<td/>').append(head))
            } else {
                $theadRow.append($('<th/>').text(head))
            }
        })
        $thead.append($theadRow)

        body.forEach(row => {
            const $bodyRow = $('<tr/>')
            row.forEach(item => {
                if (item instanceof jQuery) {
                    $bodyRow.append($('<td/>').append(item))
                } else {
                    $bodyRow.append($('<td/>').text(item))
                }
            })
            $tbody.append($bodyRow)
        })

        $table.append($caption, $thead, $tbody)
        if (options.responsive) {
            options.responsiveBreakpoint = options.responsiveBreakpoint !== undefined ? options.responsiveBreakpoint : ''
            $table = $('<div/>').addClass(options.responsiveBreakpoint !== undefined ? `table-responsive-${options.responsiveBreakpoint}` : 'table-responsive').append($table)
        }
        return $table
    }
}