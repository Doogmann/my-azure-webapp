#!/bin/bash

# Azure resource variables
resource_group=MyWebAppRG
vm_name=MyWebAppVM
location=northeurope

echo "Creating resource group..."
az group create --location $location --name $resource_group

echo "Creating VM with nginx..."
az vm create --name $vm_name \
             --resource-group $resource_group \
             --image Ubuntu2204 \
             --size Standard_B1s \
             --admin-username azureuser \
             --generate-ssh-keys \
             --custom-data @cloud-init-nginx.yaml

echo "Opening HTTP port..."
az vm open-port --port 80 \
                --resource-group $resource_group \
                --name $vm_name \
                --priority 100

echo "VM Public IP:"
az vm show --resource-group $resource_group \
           --name $vm_name \
           --show-details \
           --query publicIps \
           --output tsv
