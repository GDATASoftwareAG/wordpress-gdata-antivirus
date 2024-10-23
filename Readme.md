# Antivirus with G DATA VaaS Integration

## Licensing and Partnerships

This plugin is freely available for individual and small business users, providing a high level of security for your WordPress site. [You can get your credentials via our landing page](https://www.gdata.de/vaas-files/vaas-technical-onboarding.html)

For commercial entities, we offer the opportunity to secure your customers' sites. This partnership not only enhances your security offerings but also demonstrates your commitment to customer safety. Interested organizations are encouraged to [contact us for more details](mailto:oem@gdata.de) on how to leverage this powerful antivirus solution.

## Overview

Welcome to the WordPress Antivirus Plugin (gdata-antivirus) with G DATA VaaS integration! This plugin adds an additional layer of security to your WordPress installation by utilizing the powerful antivirus service from G DATA CyberDefense AG, known as VaaS (Verdict as a Service). This integration ensures that your WordPress website remains protected from potential threats and malware.

## Features

- **Real-time Scanning:** The plugin performs real-time scanning of file uploads through WordPress upload forms (media, plugin or theme uploads), preventing malicious content from entering your website.
  
- **Full WordPress Scan:** Conduct a comprehensive scan of your entire WordPress installation to identify and eliminate any existing malware.

- **Post Scans:** Scan blog posts done by your site authors.

- **Comment Scans:** Even the comments of your users can be checked for viruses.

- **Full WordPress Scan:** Conduct a comprehensive scan of your entire WordPress installation to identify and eliminate any existing malware.


- **G DATA VaaS Integration:** The antivirus service powering this plugin is provided by G DATA CyberDefense AG, a leading cybersecurity company known for its advanced threat detection capabilities.

## Usage

### Real-time Scan

The plugin seamlessly integrates with WordPress upload (like the media upload in the admin page) forms to scan files in real-time during the upload process. Any potential threats are detected and neutralized before they can harm your website.

### Full WordPress Scan

Perform a full scan of your WordPress installation at any time to thoroughly examine all files and directories. This includes core files, themes, plugins, and any custom uploads, providing a comprehensive security audit.

### Scheduled scan

The scheduled scan feature of the WordPress Antivirus Plugin with G DATA VaaS Integration allows users to automate the process of scanning their entire WordPress installation for potential threats and malware. You can configure it to run every day on a selected time.

## Support and Feedback

For any issues, questions, or feedback we have two channels [our vaas e-mail contact](oem@gdata.de) or leave us an issue on our [gitub-repository](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus/issues). We appreciate your input and are committed to continually improving the plugin to meet your security needs.

## License

This WordPress Antivirus Plugin is released under the [GNU General Public License v3.0](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus/blob/main/LICENSE). Feel free to contribute, share, and modify the plugin within the terms of the license.

## Contribution

Contributions are welcome! To get started with development, please use the provided devcontainer. This will ensure that you have a consistent development environment that's ready to go.

### Using the Devcontainer

The devcontainer is configured to mount the source code into the containers. This allows you to work on the code from your local machine, while running and testing it in an environment that closely matches the deployment environment.

Within the devcontainer it starts a wordpress-environment with `docker composer` within that you can even debug the code running directly in wordpress. 

### How to rebuild within the container

When you change code, it is not instantly put into the container because the directory that is actually mounted is the scoped-code directory.
When starting something bigger you can just set the mount-point in the ./compose.yml and the `/var/www/html/wp-content/plugins/gdata-antivirus` in the ./.vscode/launch.json to the working directory meaning `.` or full path `/workspaces/wordpress-gdata-antivirus`.

Doing this, you still have to test your changes with the scoped code, so basically reset your changes and rebuild the container.

To avoid rebuilding the container on every change you can also just run the ./.devcontainer/configureWordPress.sh script with the simple `source .devcontainer/configureWordPress.sh` command. This will run the scoper and restart the composed containers.

### Switch to live development mode

To switch to a mode, where your changes are directly affecting the running wordpress container we provide the switch-live-develop-mode.sh script. Running this the first time will change the .vscode/launch.json and compose.yml files, so that the code in the root folder is directly mounted into the container and the debugger also points to this code (if you have a debugger running you have to restart it once).

When running this script within a running container, you have to run `source .devcontainer/configureWordPress.sh` once to start live mode and once when you switch back to scoped mode.

Please do not commit the code while in live mode. Just run the script again and it will reset these changes.

### Running the cron

If you want to run the cron event directly user this command.

`docker exec  --user www-data -it gdata-antivirus-app-1 bash -c "XDEBUG_CONFIG='client_port=9080 client_host=172.19.0.1' wp --debug cron event run gdatacyberdefenseag_antivirus_scan_batch"`

## Disclaimer

While this plugin enhances the security of your WordPress installation, no security measure is foolproof. Regular backups and other security best practices are still recommended to ensure the safety of your website.

Thank you for choosing the gdata-antivirus. Stay secure!