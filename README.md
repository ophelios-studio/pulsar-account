# Pulsar Account

## Abstract
Framework extension which provides core features for account management (login, signup, database structures, services, etc.).

## Installation

### Composer dependency
The first step is to include the PHP composer dependency in the project. If the 
library is private, be sure to include the repository.

```json
"repositories": [
    {
      "type": "vcs",
      "url":  "https://github.com/ophelios-studio/pulsar-account.git"
    }
]
```

```json
"require": {
    "ophelios/pulsar-account": "dev-main"
}
```

Once it's done, update the project's dependencies. Should download and place the necessary 
files into the project with the standard Publisher class.

```shell
composer update
```

## Usage

### Remember me

To allow the secure remember me feature, you need to include a checkbox field with the name "remember" on your login 
form. It will be processed automatically during the login phase.

```latte
{include "zf-field-checkbox", "Remember me on this device", "remember"}
```

Then, to automatically login users which have selected the remember option, you need to include the following 
script in the `before` method of your master Controller class.

```php
public function before(): ?Response
{
    return $this->attemptAutomatedLogin()
        ?? parent::before();
}

private function attemptAutomatedLogin(): ?Response
{
    if (!Passport::isAuthenticated()) {
        try {
            $authenticator = new Authenticator();
            if ($authenticator->automatedLogin()) {
                return $this->redirect($this->request->getRoute());
            }
        } catch (RecognizerException $exception) {
            Flash::error($exception->getMessage());
        }
    }
    return null;
}
```