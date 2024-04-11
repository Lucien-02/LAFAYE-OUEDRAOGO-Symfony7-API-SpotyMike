<?php

namespace App\Util;

class ErrorTypes
{
    public const TOO_MANY_CONNECTION_ATTEMPTS = 'TooManyConnectionAttempts';
    public const TOO_MANY_PASSWORD_ATTEMPTS = 'TooManyPasswordAttempts';
    public const MISSING_ATTRIBUTES = 'MissingAttributes';
    public const MISSING_EMAIL = 'MissingEmail';
    public const INVALID_EMAIL = 'InvalidEmail';
    public const INVALID_PASSWORD_FORMAT = 'InvalidPasswordFormat';
    public const USER_NOT_FOUND = 'UserNotFound';
    public const EMAIL_NOT_FOUND = 'EmailNotFound';
    public const ACCOUNT_NOT_ACTIVE = 'AccountNotActive';
    public const UNEXPECTED_ERROR = 'UnexpectedError';
    public const INVALID_DATE_FORMAT = 'InvalidDateFormat';
    public const INVALID_AGE = 'InvalidAge';
    public const INVALID_PHONE_NUMBER = 'InvalidPhoneNumber';
    public const INVALID_GENDER = 'InvalidGender';
    public const NOT_UNIQUE_EMAIL = 'NotUniqueEmail';
}
