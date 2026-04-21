import { genPassword } from "../helpers.js";

export function initRestApi() {
    console.info("RaspAP restapi module initialized");

    $(document).on("click", "#gen_apikey", function(e) {
        $('#txtapikey').val(genPassword(32).toLowerCase());
    });
}