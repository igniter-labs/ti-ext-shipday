<div class="mt-3 p-3 border rounded">
    <h4>Get your credentials</h4>
    <ol class="pl-3">
        <li>Login to your <a
                target="_blank"
                href="https://dispatch.shipday.com/dashboard#accountInfo"
            >Shipday account</a> to get the Shipday API key under integrations tab. Under,
            <b>Main Menu > My Account > Account</b>
        </li>
        <li>Click Save Changes.</li>
        <li>That's it! Shipday has been configured!</li>
    </ol>
    <div>
        Webhook callback URL: <code>
            {{ route('igniterlabs_shipday_webhook', $formModel->webhook_token) }}
        </code>
    </div>
</div>
