document.addEventListener('DOMContentLoaded', function () {
    const authMethodSelect = document.getElementById('wordpress_gdata_antivirus_credentials_authentication_method');
    const clientCredentialsClientIdField = document.getElementById('wordpress_gdata_antivirus_credentials_client_id').parentElement.parentElement;
    const clientCredentialsClientSecretField = document.getElementById('wordpress_gdata_antivirus_credentials_client_secret').parentElement.parentElement;
    const resourceOwnerUsernameField = document.getElementById('wordpress_gdata_antivirus_credentials_username').parentElement.parentElement;
    const resourceOwnerPasswordField = document.getElementById('wordpress_gdata_antivirus_credentials_password').parentElement.parentElement;

    function toggleAuthFields() {
        console.log(authMethodSelect.value);
        const method = authMethodSelect.value;
        if (method === 'ClientCredentialsGrant') {
            console.log("set: ClientCredentialsGrant");
            resourceOwnerUsernameField.style.visibility = 'collapse';
            resourceOwnerPasswordField.style.visibility = 'collapse';
            clientCredentialsClientIdField.style.visibility = 'visible';
            clientCredentialsClientSecretField.style.visibility = 'visible';
        } else if (method === 'ResourceOwnerPasswordGrant') {
            console.log("set: ResourceOwnerPasswordGrant");
            clientCredentialsClientIdField.style.visibility = 'collapse';
            clientCredentialsClientSecretField.style.visibility = 'collapse';
            resourceOwnerUsernameField.style.visibility = 'visible';
            resourceOwnerPasswordField.style.visibility = 'visible';
        }
    }

    // Initial toggle on page load
    toggleAuthFields();

    // Add change event listener
    authMethodSelect.addEventListener('change', toggleAuthFields);
});