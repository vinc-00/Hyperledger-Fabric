#!/bin/bash

#all the fields with this structure: "!something..." will be replaced at run-time with the appropriate attributes

#Go into home directory
cd $HOME

#Hyperledger Fabric Home variable
HFH=Hyperledger_Fabric_Network/Fabric

#create the required folders and download the binary
mkdir Hyperledger_Fabric_Network
mkdir Hyperledger_Fabric_Network/Fabric
cd Hyperledger_Fabric_Network
curl -sSLO https://raw.githubusercontent.com/hyperledger/fabric/main/scripts/install-fabric.sh && chmod +x install-fabric.sh
./install-fabric.sh samples binary
cd $HOME
mkdir $HFH/fabric-ca-client
mkdir $HFH/fabric-ca-client/!CA_NAME && mkdir $HFH/fabric-ca-client/tls-ca && mkdir $HFH/fabric-ca-client/tls-root-cert
mkdir $HFH/fabric-ca-server-tls

#copy the binaries into the related folders
cp Hyperledger_Fabric_Network/fabric-samples/bin/fabric-ca-client $HFH/fabric-ca-client
cp Hyperledger_Fabric_Network/fabric-samples/bin/fabric-ca-server $HFH/fabric-ca-server-tls

#initialize the network
cd $HFH/fabric-ca-server-tls
./fabric-ca-server init -b !TLS_ADMIN_USERNAME:!TLS_ADMIN_PASSWORD

#edit the configuration file of the tls ca server
sed -i 's/port: 7054/port: !TLS_CA_SERVER_PORT/' fabric-ca-server-config.yaml #changes listening port of the tls ca server
sed -z -i 's/# Enable TLS (default: false)\n  enabled: false/# Enable TLS (default: false)\n  enabled: true/' fabric-ca-server-config.yaml #activates the TLS protocol
sed -i -z 's/# Name of this CA\n  name:/# Name of this CA\n  name: !TLS_CA_NAME/' fabric-ca-server-config.yaml 
sed -i -z 's/   hosts:/   hosts:\n     - !TLS_CA_SERVER_HOSTS/' fabric-ca-server-config.yaml  #This line is still to be fixed
sed -i -z 's/      ca:\n         usage:\n           - cert sign\n           - crl sign\n         expiry: 43800h\n         caconstraint:\n           isca: true\n           maxpathlen: 0//' fabric-ca-server-config.yaml

#remove the old certificate and the msp folder

rm -r msp
rm ca-cert.pem

cd $HOME









