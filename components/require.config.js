var components = {
    "packages": [
        {
            "name": "jplayer",
            "main": "jplayer-built.js"
        },
        {
            "name": "select2",
            "main": "select2-built.js"
        }
    ],
    "shim": {
        "jplayer": {
            "deps": [
                "jquery"
            ]
        }
    },
    "baseUrl": "components"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}