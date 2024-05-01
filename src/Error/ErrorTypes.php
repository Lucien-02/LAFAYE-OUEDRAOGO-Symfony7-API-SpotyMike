<?php

namespace App\Error;

class ErrorTypes
{
    public const TOO_MANY_CONNECTION_ATTEMPTS = 'TooManyConnectionAttempts';
    public const TOO_MANY_PASSWORD_ATTEMPTS = 'TooManyPasswordAttempts';
    public const MISSING_ATTRIBUTES = 'MissingAttributes';
    public const MISSING_ATTRIBUTES_LOGIN = 'MissingAttributesLogin';
    public const MISSING_EMAIL = 'MissingEmail';
    public const MISSING_PASSWORD = 'MissingPassword';
    public const MISSING_ALBUM_ID = 'MissingAlbumId';
    public const INVALID_EMAIL = 'InvalidEmail';
    public const INVALID_CATEGORY = 'InvalidCategory';
    public const INVALID_PASSWORD_FORMAT = 'InvalidPasswordFormat';
    public const NOT_ACTIVE_USER = 'NotActiveUser';
    public const NOT_FOUND_ARTIST = 'NotFoundArtist';
    public const NOT_FOUND_ALBUM = 'NotFoundAlbum';
    public const NOT_FOUND_USER = 'NotFoundUser';
    public const NOT_FOUND_SONG = 'NotFoundSong';
    public const NOT_FOUND_PLAYLIST = 'NotFoundPlaylist';
    public const NOT_FOUND_LABEL = 'NotFoundLabel';
    public const NOT_FOUND_ARTIST_ID = 'NotFoundArtistId';
    public const NOT_FOUND_ALBUM_ID = 'NotFoundAlbumId';
    public const NOT_FOUND_USER_ID = 'NotFoundUserId';
    public const NOT_FOUND_SONG_ID = 'NotFoundSongId';
    public const NOT_FOUND_PLAYLIST_ID = 'NotFoundPlaylistId';
    public const NOT_FOUND_LABEL_ID = 'NotFoundLabelId';
    public const USER_NOT_FOUND = 'UserNotFound';
    public const EMAIL_NOT_FOUND = 'EmailNotFound';
    public const ACCOUNT_NOT_ACTIVE = 'AccountNotActive';
    public const UNEXPECTED_ERROR = 'UnexpectedError';
    public const INVALID_DATE_FORMAT = 'InvalidDateFormat';
    public const INVALID_AGE = 'InvalidAge';
    public const INVALID_PHONE_NUMBER = 'InvalidPhoneNumber';
    public const INVALID_GENDER = 'InvalidGender';
    public const INVALID_PAGE = 'InvalidPage';
    public const NOT_UNIQUE_EMAIL = 'NotUniqueEmail';
    public const NOT_UNIQUE_ALBUM_TITLE = 'NotUniqueAlbumTitle';
    public const NOT_UNIQUE_TEL = 'NotUniqueTel';
    public const NOT_UNIQUE_ARTIST_NAME = 'NotUniqueArtistName';
    public const TOKEN_INVALID_MISSING = 'TokenInvalidMissing';
    public const TOKEN_PASSWORD_EXPIRE = 'TokenPasswordExpire';
    public const ACCOUNT_ALREADY_DESACTIVATE = 'AccountAlreadyDesactivate';
    public const INVALID_DATA_LENGTH = 'InvalidDataLength';
}
