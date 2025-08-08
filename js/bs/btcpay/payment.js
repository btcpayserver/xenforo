(() => {
  window.btcpay.xfRegistry ??= {}

  window.btcpay.xf ??= {
    hasJquery: typeof jQuery !== 'undefined',

    openNextInvoice () {
      const id = btcpay.xfRegistry.nextInvoiceId
      if (!id) return

      setTimeout(this.closeOverlay)

      btcpay.xfRegistry.nextInvoiceId = ''
      btcpay.showInvoice(id)
    },

    closeOverlay () {
      const overlays = document.querySelectorAll('.overlay-container.is-active')
      if (overlays.length === 0) return

      const lastOverlay = overlays[overlays.length - 1]
      const overlay = lastOverlay.querySelector('.overlay')

      if (this.hasJquery) {
        $(overlay).trigger('overlay:hide')
      } else {
        overlay.dispatchEvent(new Event('overlay:hide'))
      }
    }
  }

  window.btcpay.xf.openNextInvoice()
})()
