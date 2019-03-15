<?php

namespace SCFL\App\Model;


use Previewtechs\PHPUtilities\UUID;

class UsersModel extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'scfl_users';

    /**
     * @var array
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email_address',
        'password',
    ];

    /**
     * @param array $postData
     * @return bool
     * @throws \Throwable
     */
    public function addUser(array $postData)
    {
        $data['uuid'] = UUID::v4();

        if (!array_key_exists('first_name', $postData)) {
            throw new \InvalidArgumentException("First name must be required");
        }

        if (!array_key_exists('last_name', $postData)) {
            throw new \InvalidArgumentException("Last name must be required");
        }

        if (!array_key_exists('email_address', $postData)) {
            throw new \InvalidArgumentException("Email address must be required");
        }

        if (!array_key_exists('password', $postData)) {
            throw new \InvalidArgumentException("Password address must be required");
        }

        if (!array_key_exists('confirm_password', $postData)) {
            throw new \InvalidArgumentException("Confirm password address must be required");
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $postData['first_name'])) {
            throw new \InvalidArgumentException("First name is not valid");
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $postData['last_name'])) {
            throw new \InvalidArgumentException("Last name is not valid");
        }

        if ($postData['confirm_password'] != $postData['password']) {
            throw new \InvalidArgumentException("Password doesn't match");
        }

        $data['first_name'] = $postData['first_name'];
        $data['last_name'] = $postData['last_name'];
        $data['email_address'] = $postData['email_address'];
        $data['password'] = password_hash($postData['password'], PASSWORD_BCRYPT);

        unset($postData['confirm_password']);

        try {
            $this->fill($data);
            $this->saveOrFail();
            return $this->details($data['uuid']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param $uuid
     * @return mixed|null
     */
    public function details($uuid)
    {
        $cacheKey = "SCFL_App_Models_User_details_" . $uuid;
        if ($this->cache) {
            $cachedData = $this->cache->get($cacheKey);
            if (!empty($cachedData)) {
                return $cachedData;
            }
        }

        $userData = $this->where("uuid", $uuid)->first();
        if (empty($userData)) {
            return null;
        }

        $this->cache ? $this->cache->set($cacheKey, $userData->toArray(), 864000) : null;
        return $userData->toArray();
    }

    /**
     * @param $emailAddress
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function userDetailByEmailAddress($emailAddress)
    {
        $cacheKey = "SCFL_App_Models_User_details_" . $emailAddress;
        if ($this->cache) {
            $cachedData = $this->cache->get($cacheKey);
            if (!empty($cachedData)) {
                return $cachedData;
            }
        }

        $userData = $this->where("email_address", $emailAddress)->first();
        if (empty($userData)) {
            return null;
        }

        $this->cache ? $this->cache->set($cacheKey, $userData->toArray(), 864000) : null;
        return $userData->toArray();
    }

    /**
     * @param $pwdToken
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserByPwdToken($pwdToken)
    {
        $cacheKey = "SCFL_App_Models_User_details_" . $pwdToken;
        if ($this->cache) {
            $cachedData = $this->cache->get($cacheKey);
            if (!empty($cachedData)) {
                return $cachedData;
            }
        }

        $userData = $this->where("pwd_reset_token", $pwdToken)->first();
        if (empty($userData)) {
            return null;
        }

        $this->cache ? $this->cache->set($cacheKey, $userData->toArray(), 864000) : null;
        return $userData->toArray();
    }

    /**
     * @param $tmp
     * @param $name
     * @param $path
     * @param null $customName
     * @return array|bool
     */
    public static function uploadImages($tmp, $name, $path, $customName = null)
    {
        $ext = explode(".", $name);
        $ext = $ext[1];
        $uploadFile = $path . $customName . '.' . $ext;
        if ($ext && $ext != '') {
            if (move_uploaded_file($tmp, $uploadFile)) {
                $name = explode('/', $uploadFile);
                $name = $name[3];
                $data = array(
                    'name' => $name,
                    'path' => $uploadFile,
                );
                return $data;
            }
            return false;
        }
    }

    /**
     * @param $emailAddress
     * @param $password
     * @return bool
     */
    public function loginProcess($emailAddress, $password)
    {
        $result = $this->where('email_address', $emailAddress)->first();

        // Authorizing user credential
        if(isset($result->password) && $result->password)
        {
            // If authorized then matching password encrption.
            if(password_verify($password, $result->password) == true)
            {
                // If password encryption matched, then return true.
                $_SESSION['auth'] = $result;
                return $result;
            }
        }

        // Otherwise return false.
        $_SESSION['auth'] = null;
        return false;
    }
}