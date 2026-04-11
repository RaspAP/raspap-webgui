import { fetchUpdateResponse } from "../ajax/about.js";

export function initAbout() {
    console.info("RaspAP About module initialized");

    $('#performupdateModal').on('shown.bs.modal', function (e) {
        fetchUpdateResponse();
    });
}