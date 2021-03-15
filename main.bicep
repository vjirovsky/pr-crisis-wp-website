param location string = resourceGroup().location
param prefix string = uniqueString(resourceGroup().id)
param cdnUrlBeforeDotAzureEdgeDotNet string


var tenantId = subscription().tenantId

resource webStorageAccount 'Microsoft.Storage/storageAccounts@2019-06-01' = {
  name: '${prefix}storage' // must be globally unique
  location: location
  kind: 'StorageV2'
  sku: {
    name: 'Standard_LRS'
  }
  properties:{
    accessTier:'Hot'
    networkAcls:{
      bypass: 'AzureServices'
      virtualNetworkRules: [

      ]
      ipRules: [
        
      ]
      defaultAction: 'Allow'
    }
    encryption: {
      services:{
        blob:{
          enabled: true
        }
      }
      keySource: 'Microsoft.Storage'
    }
  }
}


resource webStorageAccountBlobService 'Microsoft.Storage/storageAccounts/blobServices@2020-08-01-preview' = {
  name: '${webStorageAccount.name}/default'
  properties:{
    cors:{
      corsRules:[

      ]
    }
    deleteRetentionPolicy: {
      enabled: false
    }
  }
}


resource webStorageAccountBlobServiceContainer 'Microsoft.Storage/storageAccounts/blobServices/containers@2020-08-01-preview' = {
  name: '${webStorageAccount.name}/default/$web'
  properties:{
    publicAccess: 'None'
  }
}



resource webCdn 'Microsoft.Cdn/profiles@2020-04-15' = {
  name: '${prefix}cdn' // must be globally unique
  location: location
  sku: {
    name: 'Standard_Microsoft'
  }
}

var webStorageAccountStaticWeb = webStorageAccount.properties.primaryEndpoints.web

var webStorageAccountStaticWebHostnameOnly = substring(webStorageAccountStaticWeb, length('https://'), length(webStorageAccountStaticWeb)-length('https://')-1)


resource webCdnEndpoint 'Microsoft.Cdn/profiles/endpoints@2020-04-15' = {
  name: '${prefix}cdn/${cdnUrlBeforeDotAzureEdgeDotNet}' // must be globally unique
  location: location
  properties:{
    isHttpsAllowed: true
    isHttpAllowed: false
    queryStringCachingBehavior:'IgnoreQueryString'
    originHostHeader: webStorageAccountStaticWebHostnameOnly
    origins:[
      {
        name: webStorageAccount.name
        properties:{
          enabled: true
          hostName: webStorageAccountStaticWebHostnameOnly
          originHostHeader: webStorageAccountStaticWebHostnameOnly
          httpsPort: 443
          priority: 1
          weight: 1000
        }
      }
    ]
  }
}

resource symbolicname 'Microsoft.KeyVault/vaults@2019-09-01' = {
  name: '${prefix}keyvault'
  location: location
  properties: {
    tenantId: tenantId
    sku: {
      name: 'standard'
      family: 'A'
    }
    enabledForTemplateDeployment: true
    enableSoftDelete: true
    softDeleteRetentionInDays: 30
    enableRbacAuthorization: true
    enablePurgeProtection: true
  }
}


output webStorageAccountStaticWebHostnameOnly string = webStorageAccountStaticWebHostnameOnly
output webStorageAccountUrl string = webStorageAccountStaticWeb