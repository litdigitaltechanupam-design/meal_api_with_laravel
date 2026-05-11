$path = 'postman/meal-api.postman_collection.json'
$collection = Get-Content $path -Raw | ConvertFrom-Json

if (-not ($collection.variable | Where-Object { $_.key -eq 'deliveryman_token' })) {
    $collection.variable = @($collection.variable) + [pscustomobject]@{ key = 'deliveryman_token'; value = '' }
}

function New-RequestItem($name, $method, $url, $rawBody = $null) {
    $headers = @([pscustomobject]@{ key = 'Accept'; value = 'application/json' }, [pscustomobject]@{ key = 'Authorization'; value = 'Bearer {{token}}' })
    if ($null -ne $rawBody) {
        $headers = @(
            [pscustomobject]@{ key = 'Accept'; value = 'application/json' },
            [pscustomobject]@{ key = 'Content-Type'; value = 'application/json' },
            [pscustomobject]@{ key = 'Authorization'; value = 'Bearer {{token}}' }
        )
    }

    $request = [ordered]@{
        method = $method
        header = $headers
        url = $url
    }

    if ($null -ne $rawBody) {
        $request.body = [pscustomobject]@{
            mode = 'raw'
            raw = $rawBody
        }
    }

    return [pscustomobject]@{
        name = $name
        request = [pscustomobject]$request
    }
}

$loginDeliverymanRaw = @'
{
  "login": "deliveryman@example.com",
  "password": "password"
}
'@

$authFolder = $collection.item | Where-Object { $_.name -eq 'Auth' }
if ($authFolder) {
    $hasDeliverymanLogin = @($authFolder.item | Where-Object { $_.name -eq 'Login Deliveryman' }).Count -gt 0
    if (-not $hasDeliverymanLogin) {
        $authFolder.item = @($authFolder.item) + [pscustomobject]@{
            name = 'Login Deliveryman'
            request = [pscustomobject]@{
                method = 'POST'
                header = @(
                    [pscustomobject]@{ key = 'Accept'; value = 'application/json' },
                    [pscustomobject]@{ key = 'Content-Type'; value = 'application/json' }
                )
                body = [pscustomobject]@{
                    mode = 'raw'
                    raw = $loginDeliverymanRaw
                }
                url = '{{base_url}}/api/auth/login'
            }
        }
    }
}

$customerFolder = $collection.item | Where-Object { $_.name -eq 'Customer Module' }
if ($customerFolder) {
    $existing = @($customerFolder.item | ForEach-Object { $_.name })
    $toAdd = @(
        (New-RequestItem 'List Customer Notifications' 'GET' '{{base_url}}/api/customer/notifications'),
        (New-RequestItem 'Show Customer Notification' 'GET' '{{base_url}}/api/customer/notifications/1'),
        (New-RequestItem 'Mark Customer Notification Read' 'PATCH' '{{base_url}}/api/customer/notifications/1/read'),
        (New-RequestItem 'Mark All Customer Notifications Read' 'PATCH' '{{base_url}}/api/customer/notifications/read-all')
    )
    foreach ($item in $toAdd) {
        if ($existing -notcontains $item.name) {
            $customerFolder.item = @($customerFolder.item) + $item
        }
    }
}

$deliverymanFolder = $collection.item | Where-Object { $_.name -eq 'Deliveryman Module' }
if ($deliverymanFolder) {
    $existing = @($deliverymanFolder.item | ForEach-Object { $_.name })
    $toAdd = @(
        (New-RequestItem 'List Deliveryman Notifications' 'GET' '{{base_url}}/api/deliveryman/notifications'),
        (New-RequestItem 'Show Deliveryman Notification' 'GET' '{{base_url}}/api/deliveryman/notifications/1'),
        (New-RequestItem 'Mark Deliveryman Notification Read' 'PATCH' '{{base_url}}/api/deliveryman/notifications/1/read'),
        (New-RequestItem 'Mark All Deliveryman Notifications Read' 'PATCH' '{{base_url}}/api/deliveryman/notifications/read-all')
    )
    foreach ($item in $toAdd) {
        if ($existing -notcontains $item.name) {
            $deliverymanFolder.item = @($deliverymanFolder.item) + $item
        }
    }
}

$assignRaw = @'
{
  "deliveryman_id": 4,
  "note": "Assigned manually by manager"
}
'@

$managementFolder = $collection.item | Where-Object { $_.name -eq 'Management Reports' }
if ($managementFolder) {
    $existing = @($managementFolder.item | ForEach-Object { $_.name })
    $toAdd = @(
        (New-RequestItem 'List Management Notifications' 'GET' '{{base_url}}/api/management/notifications'),
        (New-RequestItem 'Show Management Notification' 'GET' '{{base_url}}/api/management/notifications/1'),
        (New-RequestItem 'Mark Management Notification Read' 'PATCH' '{{base_url}}/api/management/notifications/1/read'),
        (New-RequestItem 'Mark All Management Notifications Read' 'PATCH' '{{base_url}}/api/management/notifications/read-all'),
        (New-RequestItem 'Assign Delivery Manually' 'PATCH' '{{base_url}}/api/management/deliveries/{{delivery_id}}/assign' $assignRaw)
    )
    foreach ($item in $toAdd) {
        if ($existing -notcontains $item.name) {
            $managementFolder.item = @($managementFolder.item) + $item
        }
    }
}

$collection | ConvertTo-Json -Depth 100 | Set-Content $path
