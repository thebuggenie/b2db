# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  # Configure a single virtual machine.
  config.vm.box = "debian/contrib-stretch64"
  config.vm.hostname = "b2db"

  # Use Ansible for provisining the virtual machine. Use local provisioning in
  # order not to polute the user's host system.
  config.vm.provision "ansible_local" do |ansible|
    ansible.playbook = "ansible/provision.yml"
    ansible.install_mode = "pip"
    ansible.version = "2.7.5"
    ansible.compatibility_mode = "2.0"
  end
end
