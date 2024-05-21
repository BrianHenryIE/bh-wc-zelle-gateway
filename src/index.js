import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';

// Determine the plugin slug from the current script's <script> tag `id` attribute.
// Where the script handle used is `plugin-slug-licence`.
// `<script id="script-handle-js" ...>`
const pluginSlug = (function(){
    var elements = document.querySelectorAll('script');
    var currentScript = elements[elements.length - 1];
    return currentScript.id.match(/(.*).{11}$/)[1];
})();
// Alternatively, the plugin slug should be in `document.baseURI`.

// Convert `plugin-slug-licence` to `pluginSlugLicence`.
// E.g. `bhWcZelleGatewayLicence`.
const pluginLicenceDataVarName = (function(pluginSlug){
    return pluginSlug
        .toLowerCase()
        .replace(/([-_][a-z])/g, (ltr) => ltr.toUpperCase())
        .replace(/[^a-zA-Z]/g, '')
})(pluginSlug + '-licence');

// The data seeded in PHP using `wp_add_inline_script()`.
// ajaxUrl, nonce, licence_information.
const pluginLicenceData = eval(pluginLicenceDataVarName);

const root = ReactDOM.createRoot(document.getElementById('section-licence'));
root.render(
    <React.StrictMode>
        <App licenceData={pluginLicenceData}/>
    </React.StrictMode>
);
