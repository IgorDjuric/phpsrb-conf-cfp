<?php

namespace OpenCFP\Http\Form;

/**
 * Form object for our signup & profile pages, handles validation of form data
 */
class SignupForm extends Form
{
    protected $_fieldList = [
        'email',
        'password',
        'password2',
        'first_name',
        'last_name',
        'company',
        'twitter',
        'linkedIn',
        'country',
        'companyUrl',
        'jobTitle',
        'speaker_info',
        'speaker_bio',
        'transportation',
        'hotel',
        'companyUrl',
        'speaker_photo',
        'agree_coc',
        'url',
    ];

    /**
     * Validate all methods by calling all our validation methods
     *
     * @param string $action
     *
     * @return bool
     */
    public function validateAll($action = 'create')
    {
        $this->sanitize();
        $valid_passwords = true;
        $agree_coc = true;

        if ($action == 'create') {
            $valid_passwords = $this->validatePasswords();
            $agree_coc = $this->validateAgreeCoc();
        }

        $valid_email = $this->validateEmail();
        $valid_first_name = $this->validateFirstName();
        $valid_last_name = $this->validateLastName();
        $valid_company = $this->validateCompany();
        $valid_twitter = $this->validateTwitter();
        $valid_url = $this->validateUrl();
        $valid_speaker_photo = $this->validateSpeakerPhoto();
        $valid_speaker_info = true;
        $valid_speaker_bio = true;

        if (!empty($this->_taintedData['speaker_info'])) {
            $valid_speaker_info = $this->validateSpeakerInfo();
        }

        if (!empty($this->_taintedData['speaker_bio'])) {
            $valid_speaker_bio = $this->validateSpeakerBio();
        }

        return (
            $valid_email &&
            $valid_passwords &&
            $valid_first_name &&
            $valid_last_name &&
            $valid_company &&
            $valid_twitter &&
            $valid_url &&
            $valid_speaker_info &&
            $valid_speaker_bio &&
            $valid_speaker_photo &&
            $agree_coc
        );
    }

    public function validateSpeakerPhoto(): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
        ];

        // Speaker Photo is not required, only validate if it exists
        if (!isset($this->_taintedData['speaker_photo'])) {
            return true;
        }

        // Check if the file was uploaded OK, display any error that may have occurred
        if (!$this->_taintedData['speaker_photo']->isValid()) {
            $this->_addErrorMessage($this->_taintedData['speaker_photo']->getErrorMessage());

            return false;
        }

        // Check if uploaded file is greater than 5MB
        if ($this->_taintedData['speaker_photo']->getClientSize() > (5 * 1048576)) {
            $this->_addErrorMessage('Speaker photo can not be larger than 5MB');

            return false;
        }

        // Check if photo is in the mime-type white list
        if (!in_array($this->_taintedData['speaker_photo']->getMimeType(), $allowedMimeTypes)) {
            $this->_addErrorMessage('Speaker photo must be a jpg or png');

            return false;
        }

        return true;
    }

    /**
     * Method that applies validation rules to email
     *
     * @return bool
     *
     * @internal param string $email
     */
    public function validateEmail(): bool
    {
        if (!isset($this->_taintedData['email']) || $this->_taintedData['email'] == '') {
            $this->_addErrorMessage('Missing email');

            return false;
        }

        $response = filter_var($this->_taintedData['email'], FILTER_VALIDATE_EMAIL);

        if (!$response) {
            $this->_addErrorMessage('Invalid email address format');

            return false;
        }

        return true;
    }

    /**
     * Method that applies validation rules to user-submitted passwords
     *
     * @return true|string
     */
    public function validatePasswords(): bool
    {
        $passwd = $this->_cleanData['password'];
        $passwd2 = $this->_cleanData['password2'];

        if ($passwd == '' || $passwd2 == '') {
            $this->_addErrorMessage('Missing passwords');

            return false;
        }

        if ($passwd !== $passwd2) {
            $this->_addErrorMessage('The submitted passwords do not match');

            return false;
        }

        if (strlen($passwd) < 5 && strlen($passwd2) < 5) {
            $this->_addErrorMessage('The submitted password must be at least 5 characters long');

            return false;
        }

        if ($passwd !== str_replace(' ', '', $passwd)) {
            $this->_addErrorMessage('The submitted password contains invalid characters');

            return false;
        }

        return true;
    }

    /**
     * Method that applies vaidation rules to user-submitted first names
     *
     * @return bool
     */
    public function validateFirstName(): bool
    {
        $first_name = $this->_cleanData['first_name'];
        $validation_response = true;

        if (empty($first_name)) {
            $this->_addErrorMessage('First name cannot be blank');
            $validation_response = false;
        }

        if (strlen($first_name) > 255) {
            $this->_addErrorMessage('First name cannot exceed 255 characters');
            $validation_response = false;
        }

        if ($first_name !== $this->_taintedData['first_name']) {
            $this->_addErrorMessage('First name contains unwanted characters');
            $validation_response = false;
        }

        return $validation_response;
    }

    /**
     * Method that applies vaidation rules to user-submitted first names
     *
     * @return bool
     */
    public function validateLastName(): bool
    {
        $last_name = $this->_cleanData['last_name'];
        $validation_response = true;

        if (empty($last_name)) {
            $this->_addErrorMessage('Last name cannot be blank');
            $validation_response = false;
        }

        if (strlen($last_name) > 255) {
            $this->_addErrorMessage('Last name cannot exceed 255 characters');
            $validation_response = false;
        }

        if ($last_name !== $this->_taintedData['last_name']) {
            $this->_addErrorMessage('Last name contains unwanted characters');
            $validation_response = false;
        }

        return $validation_response;
    }

    public function validateCompany(): bool
    {
        // $company = $this->_cleanData['company'];
        return true;
    }

    public function validateTwitter(): bool
    {
        // $twitter = $this->_cleanData['twitter'];
        return true;
    }

    public function validateUrl(): bool
    {
        if (preg_match('/https:\/\/joind\.in\/user\/[a-zA-Z0-9]{1,25}/', $this->_cleanData['url'])
            || !isset($this->_cleanData['url'])
            || $this->_cleanData['url'] == ''
        ) {
            return true;
        } else {
            $this->_addErrorMessage('You did not enter a valid joind.in URL');
            return false;
        }
    }

    /**
     * Method that applies validation rules to user-submitted speaker info
     *
     * @return bool
     */
    public function validateSpeakerInfo(): bool
    {
        $speakerInfo = filter_var(
            $this->_cleanData['speaker_info'],
            FILTER_SANITIZE_STRING
        );
        $validation_response = true;
        $speakerInfo = strip_tags($speakerInfo);
        $speakerInfo = $this->_purifier->purify($speakerInfo);

        if (empty($speakerInfo)) {
            $this->_addErrorMessage('You submitted speaker info but it was empty after sanitizing');
            $validation_response = false;
        }

        return $validation_response;
    }

    /**
     * Method that applies validation rules to user-submitted speaker bio
     *
     * @return bool
     */
    public function validateSpeakerBio(): bool
    {
        $speaker_bio = filter_var(
            $this->_cleanData['speaker_bio'],
            FILTER_SANITIZE_STRING
        );
        $validation_response = true;
        $speaker_bio = strip_tags($speaker_bio);
        $speaker_bio = $this->_purifier->purify($speaker_bio);

        if (empty($speaker_bio)) {
            $this->_addErrorMessage('You submitted speaker bio information but it was empty after sanitizing');
            $validation_response = false;
        }

        return $validation_response;
    }

    /**
     * Santize all our fields that were submitted
     */
    public function sanitize()
    {
        parent::sanitize();

        // We shouldn't be sanitizing passwords, so reset them
        if (isset($this->_taintedData['password'])) {
            $this->_cleanData['password'] = $this->_taintedData['password'];
        }

        if (isset($this->_taintedData['password2'])) {
            $this->_cleanData['password2'] = $this->_taintedData['password2'];
        }

        // Remove leading @ for twitter
        if (isset($this->_taintedData['twitter'])) {
            $this->_cleanData['twitter'] = preg_replace(
                '/^@/',
                '',
                $this->_taintedData['twitter']
            );
        }
    }

    private function validateAgreeCoc(): bool
    {
        if (!$this->getOption('has_coc')) {
            return true;
        }

        if ($this->_cleanData['agree_coc'] === 'agreed') {
            return true;
        }

        $this->_addErrorMessage('You must agree to abide by our code of conduct in order to submit');
        return false;
    }
}
