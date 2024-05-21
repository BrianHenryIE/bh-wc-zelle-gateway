import { useState } from 'react';
import { Notice, Button } from '@wordpress/components';


// if ( empty( $this->settings->get_license_server_url() ) ) {
// <div class="error notice"><p>Plugin update URL missing. Please reinstall plugin.</p></div>
// }
// * status banner
// * banner if we are on a staging site
// * licence key field
// * toggle:
//      * activate button
//      * deactivate licence button
// * licence expiry date
// * link to licence server my-account
// * link to create renewal order

// Error: licence server unknown.
// Error: licence server unreachable.
// Error: licence server unreachable since 2021-01-01.
// Licence is currently active, with a renewal date of 2021-12-31.
// Licence is active until 2021-12-31, with no automatic renewal.
// Licence is currently inactive, activate licence.
// Licence is currently inactive, with zero activations remaining.
// Licence key is invalid. Click here to purchase.
// Please enter a licence key.

// Error: own server unreachable


import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

// var currentScriptPath = currentScript.src;
// console.log(currentScriptPath);


// action =

// {$plugin_slug}_get_licence_details
// {$plugin_slug}_set_licence_key
// {$plugin_slug}_activate
// {$plugin_slug}_deactivate

// const queryParams = {
//     _ajax_nonce: {pluginLicenceDataVarName}.nonce,
//     action:  pluginSlug + '_get_licence_details'
// };
//
// apiFetch( { url: addQueryArgs( {pluginLicenceDataVarName}.ajaxUrl, queryParams ) } ).then( ( result ) => {
//     console.log( result );
// } );





function StatusNotice( props ) {
    const licenceStatus = props.licenceData.licence_details.status;

    let htmlStatus;
    let message;

    switch( licenceStatus ) {
        case 'invalid':
            htmlStatus = 'error';
            break;
        default:
            htmlStatus = 'warning';
    }
    message = "The licence is currently {licenceStatus}.";

    if( props.licenceData.licence_details.key === undefined ) {
        htmlStatus = 'notice';
        message = 'Please enter a licence key.';
    }

    return (
        <Notice status={htmlStatus} isDismissible={false}>
            {message}
        </Notice>
    )
}


function MyButton( props ) {
    const text = props.hasActiveLicence ? 'Deactivate licence' : 'Activate licence';
    return <Button onClick={props.update} className="button-primary">{text}</Button>;
}

function App( props ) {
    const [ pluginLicenceData, setPluginLicenceData ] = useState( props.licenceData )

    const [ licenceIsActive, setLicenceIsActive ] = useState( false );
    const [ licenceKey, setLicenceKey ] = useState( pluginLicenceData.licence_details.licence_key ?? '' );

    const enterKeyPrompt = "Please enter a licence key.";

    // Disable when licence server is down / invalid.
    const hasLicenceServer = true;

    const deactivateLicence = () => {
        setLicenceIsActive( false );
    }
    const activateLicence = () => {
        setLicenceIsActive( true );
    }

    function changeHandler( event ) {
        setLicenceKey( event.target.value );
    }

    return (
        <div className="licence">
            <StatusNotice licenceData={props.licenceData}/>
            <input value={licenceKey} onChange={changeHandler} placeholder={enterKeyPrompt} disabled={licenceIsActive}/>
            <MyButton update={licenceIsActive ? deactivateLicence : activateLicence} hasActiveLicence={licenceIsActive}/>
        </div>
    );
}

export default App;
