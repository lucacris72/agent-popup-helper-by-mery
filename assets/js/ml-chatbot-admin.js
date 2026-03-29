(function ($) {
  $(function () {
    $(".ml-chatbot-color-field").wpColorPicker();

    const control = $("[data-ml-chatbot-logo-control]");

    if (!control.length) {
      return;
    }

    const input = control.find("[data-ml-chatbot-logo-id]");
    const preview = control.find("[data-ml-chatbot-logo-preview]");
    let frame = null;

    const renderPreview = (url) => {
      if (!url) {
        preview.empty();
        return;
      }

      preview.html(
        `<img src="${url}" alt="" style="max-width:64px;height:auto;border-radius:12px;display:block;margin-bottom:10px;" />`
      );
    };

    control.find("[data-ml-chatbot-logo-upload]").on("click", function (event) {
      event.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: "Choose chatbot logo",
        button: {
          text: "Use this logo",
        },
        library: {
          type: "image",
        },
        multiple: false,
      });

      frame.on("select", function () {
        const attachment = frame.state().get("selection").first().toJSON();
        input.val(attachment.id);
        renderPreview(attachment.sizes?.thumbnail?.url || attachment.url);
      });

      frame.open();
    });

    control.find("[data-ml-chatbot-logo-remove]").on("click", function (event) {
      event.preventDefault();
      input.val("");
      renderPreview("");
    });
  });
})(jQuery);
