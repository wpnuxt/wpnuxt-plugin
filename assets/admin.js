jQuery(document).ready(function ($) {

  const globalAttributes = $(".global_attribute_table");
  let uniqueId = Math.random().toString(36).slice(2, 11);

  // deploy-button FROM build_hook
  const buildUrl = $("#build_url");
  $("#deploy-button").click(function (e) {
    e.preventDefault();
    $.ajax({
      url: buildUrl.val(),
      type: "POST",
      success(data) {
        alert("Build triggered successfully");
      },
    });
  });
}) 
