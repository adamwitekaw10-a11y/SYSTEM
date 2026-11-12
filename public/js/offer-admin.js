document.documentElement.classList.add("js");

(function () {
  const form = document.querySelector("[data-offer-admin-form]");
  if (!form || typeof tinymce === "undefined") return;

  const keys = ["ksiegowosc", "kadry", "pit"];

  const polish = {
    language_url: undefined,
  };

  tinymce.init({
    selector: keys.map((key) => `#editor-${key}`).join(","),
    base_url: "/vendor/tinymce",
    suffix: ".min",
    menubar: "edit insert format table",
    plugins: "lists link table autolink code",
    toolbar:
      "undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code",
    style_formats: [
      { title: "Akapit", format: "p" },
      { title: "Nagłówek 2", format: "h2" },
      { title: "Nagłówek 3", format: "h3" },
      { title: "Nagłówek 4", format: "h4" },
    ],
    block_formats: "Akapit=p; Nagłówek 2=h2; Nagłówek 3=h3; Nagłówek 4=h4",
    height: 420,
    branding: false,
    promotion: false,
    convert_urls: false,
    relative_urls: false,
    entity_encoding: "raw",
    valid_elements:
      "p,br,strong/b,em/i,u,h2,h3,h4,ul,ol,li,a[href|title|target|rel],table,thead,tbody,tfoot,tr,th[scope|colspan|rowspan],td[colspan|rowspan],blockquote,div[class],span",
    content_style:
      "body{font-family:Georgia,serif;font-size:16px;line-height:1.6;color:#2b1a22} table{border-collapse:collapse;width:100%} th,td{border:1px solid #ccc;padding:0.4rem 0.55rem}",
    setup(editor) {
      editor.on("change input undo redo", () => {
        editor.save();
      });
    },
    ...polish,
  });

  form.addEventListener("submit", () => {
    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }
  });

  const tabs = [...form.querySelectorAll("[data-offer-admin-tab]")];
  const panels = [...form.querySelectorAll("[data-offer-admin-panel]")];
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const key = tab.getAttribute("data-offer-admin-tab");
      tabs.forEach((item) => item.classList.toggle("is-active", item === tab));
      panels.forEach((panel) => {
        const match = panel.getAttribute("data-offer-admin-panel") === key;
        panel.classList.toggle("is-active", match);
        panel.hidden = !match;
      });
      const editor = tinymce.get(`editor-${key}`);
      if (editor) {
        editor.focus();
      }
    });
  });
})();
