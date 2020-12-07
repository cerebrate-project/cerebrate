function showToast(options) {
    var theToast = new Toaster(options)
    theToast.makeToast()
    theToast.show()
    return theToast.$toast
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
        return this.options.title !== false || this.options.muted !== false || this.options.body !== false
    }

    static buildToast(options) {
        var $toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true"/>')
        $toast.addClass('toast-' + options.variant)
        if (options.title !== false || options.muted !== false) {
            var $toastHeader = $('<div class="toast-header"/>')
            $toastHeader.addClass('toast-' + options.variant)
            if (options.title !== false) {
                var $toastHeaderText
                if (options.titleHtml) {
                    $toastHeaderText = $('<div class="mr-auto"/>').html(options.title);
                } else {
                    $toastHeaderText = $('<strong class="mr-auto"/>').text(options.title)
                }
                $toastHeader.append($toastHeaderText)
            }
            if (options.muted !== false) {
                var $toastHeaderMuted
                if (options.mutedHtml) {
                    $toastHeaderMuted = $('<div/>').html(options.muted)
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
        if (options.body !== false) {
            var $toastBody
            if (options.bodyHtml) {
                $toastBody = $('<div class="toast-body"/>').html(options.body)
            } else {
                $toastBody = $('<div class="toast-body"/>').text(options.body)
            }
            $toast.append($toastBody)
        }
        return $toast
    }
}
