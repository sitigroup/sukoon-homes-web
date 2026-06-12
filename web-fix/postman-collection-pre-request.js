// Collection → Scripts → Pre-request (replace entire script)

const token = (
    pm.collectionVariables.get('bearer_token')
    || pm.environment.get('bearer_token')
    || pm.collectionVariables.get('access_token')
    || pm.environment.get('access_token')
    || ''
).trim();

const url = pm.request.url.toString();
const pathParts = pm.request.url.path;
const path = Array.isArray(pathParts) ? pathParts.join('/') : String(pathParts || '');

// 8.3 — must be unauthenticated; browser-style Accept for 302 to login
const isAreaListingIndexHealthCheck =
    pm.request.method === 'GET'
    && (/\/area-listing\/?$/.test(url) || path === 'area-listing');

const needsMaintenanceAuth =
    path.includes('repair-locations')
    || path.includes('drift-check');

if (isAreaListingIndexHealthCheck) {
    pm.request.headers.remove('Authorization');
    pm.request.headers.upsert({ key: 'Accept', value: 'text/html,application/xhtml+xml' });
} else {
    if (needsMaintenanceAuth || path.includes('api/')) {
        if (!token) {
            console.warn(
                '[Area Listing] bearer_token is empty. Set Collection variable bearer_token to your Sanctum token.'
            );
        } else {
            pm.request.headers.upsert({
                key: 'Authorization',
                value: 'Bearer ' + token,
            });
            if (needsMaintenanceAuth) {
                pm.request.url.addQueryParams([
                    { key: 'token', value: token, disabled: false },
                ]);
            }
        }
    }
    pm.request.headers.upsert({ key: 'Accept', value: 'application/json' });
}
