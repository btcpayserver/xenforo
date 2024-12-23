(() => {
  window.btcpay.xfRegistry ??= {}

  window.btcpay.xf ??= {
    hasJquery: typeof jQuery !== 'undefined',

    openNextInvoice () {
      const id = btcpay.xfRegistry.nextInvoiceId
      if (!id) return

      const overlays = document.querySelectorAll('.overlay-container.is-active')
      const lastOverlay = overlays[overlays.length - 1]
      const overlay = lastOverlay.querySelector('.overlay')

      if (this.hasJquery) {
        $(overlay).trigger('overlay:hide')
      } else {
        overlay.dispatchEvent(new Event('overlay:hide'))
      }

      btcpay.xfRegistry.nextInvoiceId = ''
      btcpay.showInvoice(id)
    }
  }

  window.btcpay.xf.openNextInvoice()
})()
