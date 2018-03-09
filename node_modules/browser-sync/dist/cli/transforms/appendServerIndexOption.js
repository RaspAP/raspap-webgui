"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
function appendServerIndexOption(incoming) {
    if (!incoming.get('server'))
        return incoming;
    var value = incoming.get('index');
    if (value) {
        return incoming.setIn(['server', 'index'], value);
    }
    return incoming;
}
exports.appendServerIndexOption = appendServerIndexOption;
//# sourceMappingURL=appendServerIndexOption.js.map