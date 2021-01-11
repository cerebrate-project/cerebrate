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
    modal (options) {
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
    modalFromURL (url, POSTSuccessCallback, POSTFailCallback) {
        return AJAXApi.quickFetchURL(url).then((modalHTML) => {
            const theModal = new ModalFactory({
                rawHTML: modalHTML,
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
     * Fetch HTML from the provided URL and override the $container's content. $statusNode allows to specify another HTML node to display the loading
     * @param  {string} url - The URL from which the $container's content should be fetched
     * @param  {(jQuery|string)} $container - The container that should hold the data fetched
     * @param  {(jQuery|string)} [$statusNode=null] - A reference to a HTML node on which the loading animation should be displayed. If not provided, $container will be used
     * @return {Promise<jQuery>} Promise object resolving to the $container object after its content has been replaced
     */
    reload (url, $container, $statusNode=null) {
        $container = $($container)
        $statusNode = $($statusNode)
        if (!$statusNode) {
            $statusNode = $container
        }
        return AJAXApi.quickFetchURL(url, {
            statusNode: $statusNode[0]
        }).then((theHTML) => {
            $container.replaceWith(theHTML)
            return $container
        })
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
        if (this.options.rawHTML) {
            this.attachSubmitButtonListener = true
        }
        this.bsModalOptions = {
            show: true
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
     * @property {string} title                            - The title's content of the modal
     * @property {string} titleHtml                        - The raw HTML title's content of the modal
     * @property {string} body                             - The body's content of the modal
     * @property {string} bodyHtml                         - The raw HTML body's content of the modal
     * @property {string} rawHTML                          - The raw HTML of the whole modal. If provided, will override any other content
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} variant - The variant of the modal
     * @property {string} modalClass                       - Classes to be added to the modal's container
     * @property {string} headerClass                      - Classes to be added to the modal's header
     * @property {string} bodyClass                        - Classes to be added to the modal's body
     * @property {string} footerClass                      - Classes to be added to the modal's footer
     * @property {string=('ok-only','confirm','confirm-s   uccess','confirm-warning','confirm-danger')} type - Pre-configured template covering most use cases
     * @property {string} confirmText                      - The text to be placed in the confirm button
     * @property {string} cancelText                       - The text to be placed in the cancel button
     * @property {boolean} closeManually                   - If true, the modal will be closed automatically whenever a footer's button is pressed
     * @property {boolean} closeOnSuccess                  - If true, the modal will be closed if the $FILL_ME operation is successful
     * @property {ModalFactory~confirm} confirm                         - The callback that should be called if the user confirm the modal
     * @property {ModalFactory~cancel} cancel                           - The callback that should be called if the user cancel the modal
     * @property {ModalFactory~APIConfirm} APIConfirm                   - The callback that should be called if the user confirm the modal. Behave like the confirm option but provide an AJAXApi object that can be used to issue requests
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
        title: '',
        titleHtml: false,
        body: false,
        bodyHtml: false,
        rawHTML: false,
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
    static spinnerHtml = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...'

    /** Create the HTML of the modal and inject it into the DOM */
    makeModal() {
        if (this.isValid()) {
            this.$modal = this.buildModal()
            $('#mainModalContainer').append(this.$modal)
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
        this.options.rawHTML !== false
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
        if (this.options.rawHTML) {
            $modalDialog = $(this.options.rawHTML)
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
                .catch(() => {
                    this.options.APIError(() => { this.hide() }, this, evt)
                })
            }
        }
    }

    /** Attach the submission click listener for modals that have been generated by raw HTML */
    findSubmitButtonAndAddListener(clearOnclick=true) {
        const $submitButton = this.$modal.find('.modal-footer #submitButton')
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
            tmpApi.mergeOptions({forceHTMLOnValidationFailure: true})
            return tmpApi.postForm($form[0])
                .then((data) => {
                    if (data.success) {
                        this.options.POSTSuccessCallback(data)
                    } else { // Validation error, replace modal content with new html
                        this.$modal.html(data.html)
                        this.findSubmitButtonAndAddListener()
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

/** Class representing an loading overlay */
class OverlayFactory {
    /**
     * Create a loading overlay
     * @param {(jQuery|string)} node    - The node on which the overlay should be placed
     * @param {Object}          options - The options supported by OverlayFactory#defaultOptions 
     */
    constructor(node, options) {
        this.node = node
        this.$node = $(this.node)
        this.options = Object.assign({}, OverlayFactory.defaultOptions, options)
        if (this.options.auto) {
            this.adjustOptionsBasedOnNode()
        }
    }

    /**
     * @namespace
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} variant - The variant of the overlay
     * @property {number}  opacity        - The opacity of the overlay
     * @property {boolean} rounded        - If the overlay should be rounded
     * @property {number}  auto           - Whether overlay and spinner options should be adapted automatically based on the node
     * @property {string=('primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark'|'white'|'transparent')} spinnerVariant - The variant of the spinner
     * @property {boolean} spinnerSmall   - If the spinner inside the overlay should be small
     */
    static defaultOptions = {
        variant: 'light',
        opacity: 0.85,
        blur: '2px',
        rounded: false,
        auto: true,
        spinnerVariant: '',
        spinnerSmall: false,
    }

    static overlayWrapper = '<div aria-busy="true" class="position-relative"/>'
    static overlayContainer = '<div class="position-absolute" style="inset: 0px; z-index: 10;"/>'
    static overlayBg = '<div class="position-absolute" style="inset: 0px;"/>'
    static overlaySpinner = '<div class="position-absolute" style="top: 50%; left: 50%; transform: translateX(-50%) translateY(-50%);"><span aria-hidden="true" class="spinner-border"><!----></span></div></div>'

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
        if (this.options.spinnerSmall) {
            this.$overlaySpinner.children().addClass('spinner-border-sm')
        }
        if (this.options.spinnerVariant.length > 0) {
            this.$overlaySpinner.children().addClass(`text-${this.options.spinnerVariant}`)
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
        if (this.$node.is('input[type="checkbox"]')) {
            this.options.rounded = true
        } else {
            let classes = this.$node.attr('class')
            if (classes !== undefined) {
                classes = classes.split(' ')
                this.options.spinnerVariant = OverlayFactory.detectedBootstrapVariant(classes)
            }
        }
    }

    /**
     * Detect the bootstrap variant from a list of classes
     * @param {Array} classes - A list of classes containg a bootstrap variant 
     */
    static detectedBootstrapVariant(classes) {
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
        return '';
    }
}