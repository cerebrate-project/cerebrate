
class UIFactory {
    /* Display a toast based on provided options */
    toast(options) {
        const theToast = new Toaster(options);
        theToast.makeToast()
        theToast.show()
        return theToast
    }

    /* Display a modal based on provided options */
    modal (options) {
        const theModal = new ModalFactory(options);
        theModal.makeModal()
        theModal.show()
        return theModal
    }

    /* Display a modal based on provided options */
    modalFromURL (url, successCallback, failCallback) {
        return AJAXApi.quickFetchURL(url).then((modalHTML) => {
            const theModal = new ModalFactory({
                rawHTML: modalHTML,
                replaceFormSubmissionByAjax: true,
                successCallback: successCallback !== undefined ? successCallback : () => {},
                failCallback: failCallback !== undefined ? failCallback : (errorMessage) => {},
            });
            theModal.makeModal(modalHTML)
            theModal.show()
            theModal.$modal.data('modalObject', theModal)
            return theModal
        })
    }

    /* Fetch HTML from the provided URL and override content of $container. $statusNode allows to specify another HTML node to display the loading */
    reload (url, $container, $statusNode=null) {
        $container = $($container)
        $statusNode = $($statusNode)
        if (!$statusNode) {
            $statusNode = $container
        }
        AJAXApi.quickFetchURL(url, {
            statusNode: $statusNode[0]
        }).then((data) => {
            $container.replaceWith(data)
        })
    }
}

class Toaster {
    constructor(options) {
        this.options = Object.assign({}, Toaster.defaultOptions, options)
        this.bsToastOptions = {
            autohide: this.options.autohide,
            delay: this.options.delay,
        }
    }

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

    makeToast() {
        if (this.isValid()) {
            this.$toast = Toaster.buildToast(this.options)
            $('#mainToastContainer').append(this.$toast)
        }
    }

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

    removeToast() {
        this.$toast.remove();
    }

    isValid() {
        return this.options.title !== false || this.options.muted !== false || this.options.body !== false || this.options.titleHtml !== false || this.options.mutedHtml !== false || this.options.bodyHtml !== false
    }

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

class ModalFactory {
    constructor(options) {
        this.options = Object.assign({}, ModalFactory.defaultOptions, options)
        this.bsModalOptions = {
            show: true
        }
    }

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
        modalClass: [],
        headerClass: [],
        bodyClass: [],
        footerClass: [],
        buttons: [],
        type: 'ok-only',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        closeManually: false,
        closeOnSuccess: true,
        confirm: function() {},
        APIConfirm: null,
        cancel: function() {},
        error: function() {},
        shownCallback: function() {},
        hiddenCallback: function() {},
        successCallback: function() {},
        replaceFormSubmissionByAjax: false
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

    makeModal() {
        if (this.isValid()) {
            this.$modal = this.buildModal()
            $('#mainModalContainer').append(this.$modal)
        }
    }

    show() {
        if (this.isValid()) {
            var that = this
            this.$modal.modal(this.bsModalOptions)
                .on('hidden.bs.modal', function () {
                    that.removeModal()
                    that.options.hiddenCallback()
                })
                .on('shown.bs.modal', function () {
                    that.options.shownCallback()
                    if (that.options.replaceFormSubmissionByAjax) {
                        that.replaceFormSubmissionByAjax()
                    }
                })
        }
    }

    hide() {
        this.$modal.modal('hide')
    }

    removeModal() {
        this.$modal.remove();
    }

    isValid() {
        return this.options.title !== false || this.options.body !== false || this.options.titleHtml !== false || this.options.bodyHtml !== false || this.options.rawHTML !== false
    }

    buildModal() {
        const $modal = $('<div class="modal fade" tabindex="-1" aria-hidden="true"/>')
        if (this.options.id !== false) {
            $modal.attr('id', this.options.id)
            $modal.attr('aria-labelledby', this.options.id)
        }
        if (this.options.modalClass !== false) {
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
            $modalFooter.append(this.getFooterBasedOnType())
            $modalContent.append($modalFooter)
    
            $modalDialog.append($modalContent)
        }
        $modal.append($modalDialog)
        return $modal
    }

    getFooterBasedOnType() {
        if (this.options.type == 'ok-only') {
            return this.getFooterOkOnly()
        } else if (this.options.type.includes('confirm')) {
            return this.getFooterConfirm()
        } else {
            return this.getFooterOkOnly()
        }
    }

    getFooterOkOnly() {
        return [
            $('<button type="button" class="btn btn-primary">OK</button>')
                .attr('data-dismiss', 'modal'),
        ]
    }

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

    static getCloseButton() {
        return $(ModalFactory.closeButtonHtml)
    }

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
                    this.options.error(() => { this.hide() }, this, evt)
                })
            }
        }
    }

    replaceFormSubmissionByAjax() {
        const $submitButton = this.$modal.find('.modal-footer #submitButton')
        const formID = $submitButton.data('form-id')
        let $form
        if (formID) {
            $form = $(formID)
        } else {
            $form = this.$modal.find('form')
        }

        this.options.APIConfirm = (tmpApi) => {
            tmpApi.mergeOptions({renderedHTMLOnFailureRequested: true})
            return tmpApi.postForm($form[0])
                .then((data) => {
                    if (data.success) {
                        this.options.successCallback(data)
                    } else { // Validation error, replace modal content with new html
                        this.$modal.html(data.html)
                        this.replaceFormSubmissionByAjax()
                        return Promise.reject('Validation error');
                    }
                })
                .catch((errorMessage, response) => {
                    this.options.failCallback(errorMessage)
                    return Promise.reject(errorMessage);
                })
        }
        $submitButton.click(this.getConfirmationHandlerFunction())
    }
}

class OverlayFactory {
    constructor(options) {
        this.options = Object.assign({}, OverlayFactory.defaultOptions, options)
        if (this.options.spinnerAuto) {
            this.adjustSpinnerOptionsBasedOnNode()
        }
    }

    static defaultOptions = {
        node: false,
        variant: 'light',
        opacity: 0.85,
        blur: '2px',
        rounded: false,
        spinnerVariant: '',
        spinnerSmall: false,
        spinnerAuto: true
    }

    static overlayWrapper = '<div aria-busy="true" class="position-relative"/>'
    static overlayContainer = '<div class="position-absolute" style="inset: 0px; z-index: 10;"/>'
    static overlayBg = '<div class="position-absolute" style="inset: 0px;"/>'
    static overlaySpinner = '<div class="position-absolute" style="top: 50%; left: 50%; transform: translateX(-50%) translateY(-50%);"><span aria-hidden="true" class="spinner-border"><!----></span></div></div>'

    shown = false
    originalNodeIndex = 0

    isValid() {
        return this.options.node !== false
    }

    buildOverlay() {
        this.$node = $(this.options.node)
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

    show() {
        if (this.isValid()) {
            this.buildOverlay()
            this.mountOverlay()
            this.shown = true
        }
    }

    hide() {
        if (this.isValid() && this.shown) {
            this.unmountOverlay()
        }
        this.shown = false
    }

    mountOverlay() {
        this.originalNodeIndex = this.$node.index()
        this.$overlayBg.appendTo(this.$overlayContainer)
        this.$overlaySpinner.appendTo(this.$overlayContainer)
        this.appendToIndex(this.$overlayWrapper, this.$node.parent(), this.originalNodeIndex)
        this.$overlayContainer.appendTo(this.$overlayWrapper)
        this.$node.prependTo(this.$overlayWrapper)
    }

    unmountOverlay() {
        this.appendToIndex(this.$node, this.$overlayWrapper.parent(), this.originalNodeIndex)
        this.$overlayWrapper.remove()
        this.originalNodeIndex = 0
    }
    
    appendToIndex($node, $targetContainer, index) {
        const $target = $targetContainer.children().eq(index);
        $node.insertBefore($target);
    }

    adjustSpinnerOptionsBasedOnNode() {
        let $node = $(this.options.node)
        if ($node.width() < 50 || $node.height() < 50) {
            this.options.spinnerSmall = true
        }
        if ($node.is('input[type="checkbox"]')) {
            this.options.rounded = true
        } else {
            let classes = $node.attr('class')
            if (classes !== undefined) {
                classes = classes.split(' ')
                this.options.spinnerVariant = OverlayFactory.detectedBootstrapVariant(classes)
            }
        }
    }

    static detectedBootstrapVariant(classes) {
        const re = /^[a-zA-Z]+-(?<variant>primary|success)$/;
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