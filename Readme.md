# WordPress Antivirus Plugin with G DATA VaaS Integration

## Overview

Welcome to the WordPress Antivirus Plugin (wordpress-gdata-antivirus) with G DATA VaaS integration! This plugin adds an additional layer of security to your WordPress installation by utilizing the powerful antivirus service from G DATA CyberDefense AG, known as VaaS (Verdict as a Service). This integration ensures that your WordPress website remains protected from potential threats and malware.

## Features

- **Real-time Scanning:** The plugin performs real-time scanning of file uploads through WordPress upload forms, preventing malicious content from entering your website.
  
- **Full WordPress Scan:** Conduct a comprehensive scan of your entire WordPress installation to identify and eliminate any existing malware.

- **G DATA VaaS Integration:** The antivirus service powering this plugin is provided by G DATA CyberDefense AG, a leading cybersecurity company known for its advanced threat detection capabilities.

## Installation

1. **Download the Plugin:** Download the plugin zip file from the [GitHub repository](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus).

2. **Upload and Activate:** Upload the plugin to your WordPress installation and activate it through the WordPress dashboard.

3. **Configure Settings:** Access the plugin settings in the WordPress dashboard to to enter your VaaS Credentials.

4. **Get Credentials:** Please contact oem@gdata.de to obtain your credentials.

## Usage

### Real-time Scan

The plugin seamlessly integrates with WordPress upload (like the media upload in the admin page) forms to scan files in real-time during the upload process. Any potential threats are detected and neutralized before they can harm your website.

### Full WordPress Scan

Perform a full scan of your WordPress installation at any time to thoroughly examine all files and directories. This includes core files, themes, plugins, and any custom uploads, providing a comprehensive security audit.

## Support and Feedback

For any issues, questions, or feedback, please contact oem@gdata.de. We appreciate your input and are committed to continually improving the plugin to meet your security needs.

## License

This WordPress Antivirus Plugin is released under the [GNU General Public License v3.0](https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus/blob/main/LICENSE). Feel free to contribute, share, and modify the plugin within the terms of the license.

## Contribution

Contributions are welcome! To get started with development, please use the provided devcontainer. This will ensure that you have a consistent development environment that's ready to go.

### Using the Devcontainer

The devcontainer is configured to mount the source code into the containers. This allows you to work on the code from your local machine, while running and testing it in an environment that closely matches the deployment environment.

### Caveat

Please note that when running a full scan in WordPress, the scan will be performed twice. This is due to the way the source code is mounted into the containers. While this does not affect the functionality of the scan, it does mean that scans will take approximately twice as long to complete.

## Disclaimer

While this plugin enhances the security of your WordPress installation, no security measure is foolproof. Regular backups and other security best practices are still recommended to ensure the safety of your website.

Thank you for choosing the wordpress-gdata-antivirus. Stay secure!