document.addEventListener("DOMContentLoaded", function () {
  if (
    window.wc?.wcBlocksRegistry?.registerPaymentMethod &&
    window.wp?.element
  ) {
    const { createElement } = window.wp.element;

    window.wc.wcBlocksRegistry.registerPaymentMethod({
      name: "hexakode_banktransfer",
      label: createElement("span", null, "Bank Transfer"),
      ariaLabel: "Bank Transfer Payment",
      canMakePayment: () => Promise.resolve(true),
      content: createElement(
        "p",
        null,
        "Pay via bank transfer and upload your receipt after checkout."
      ),
      edit: createElement("p", null, "Pay via bank transfer."),
      save: null,
      supports: {
        features: ["products", "subscriptions", "default", "virtual"],
      },
    });
  }
});
