<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';
$app = new \Slim\App;

$app->post('/user/create', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $stat = $conn->prepare($sql);
        $stat->execute([':username' => $usr, ':password' => hash('SHA256', $pass)]);

        $response->getBody()->write(json_encode(array("status" => "success", "message" => "User registered successfully")));
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    return $response;
});

$app->post('/user/authorize', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $usr = isset($data->username) ? $data->username : null;
    $pass = isset($data->password) ? $data->password : null;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key = 'server_secret_key';

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($usr && $pass) {
            
            $sql = "SELECT * FROM users WHERE username = :username AND password = :password";
            $stat = $conn->prepare($sql);
            $stat->execute([':username' => $usr, ':password' => hash('SHA256', $pass)]);
            $stat->setFetchMode(PDO::FETCH_ASSOC);
            $user = $stat->fetch();

            if ($user) {
                
                $iat = time();
                $exp = $iat + 300; 
                $payload = [
                    'iss' => 'http://library.com',
                    'iat' => $iat,
                    'exp' => $exp,
                    'data' => [
                        'userid' => $user['userid'],
                        'username' => $user['username'],
                        'single_use' => true 
                    ]
                ];

                $singleUseToken = JWT::encode($payload, $key, 'HS256');

                
                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                $stat = $conn->prepare($sql);
                $stat->execute([':token' => $singleUseToken, ':userid' => $user['userid']]);

                
                $response->getBody()->write(json_encode(array("status" => "success", "token" => $singleUseToken)));
            } else {
                
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Authentication Failed"))));
            }
        } else {
            
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Credentials required"))));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Database Error"))));
    }

    return $response;
});

$app->post('/user/update', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $userid = isset($data->userid) ? $data->userid : null;  
    $usr = isset($data->username) ? $data->username : null;  
    $pass = isset($data->password) ? $data->password : null;  
    $token = isset($data->token) ? $data->token : null;  
    $key = 'server_secret_key';

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                   
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                        
                        if ($userid) {
                            $sql = "UPDATE users SET ";
                            $params = [];

                            if ($usr) {
                                $sql .= "username = :username ";
                                $params[':username'] = $usr;
                            }

                            if ($pass) {
                                if ($usr) {
                                    $sql .= ", ";
                                }
                                $sql .= "password = :password ";
                                $params[':password'] = hash('SHA256', $pass);
                            }

                            if (!empty($params)) {
                                $sql .= "WHERE userid = :userid";
                                $params[':userid'] = $userid;

                                $stat = $conn->prepare($sql);
                                $stat->execute($params);

                                if ($stat->rowCount() > 0) {
                                    
                                    $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                                    $stat = $conn->prepare($sql);
                                    $stat->execute([':userid' => $userid]);

                                    
                                    $iat = time();
                                    $exp = $iat + 300; 
                                    $payload = [
                                        'iss' => 'http://library.com',
                                        'iat' => $iat,
                                        'exp' => $exp,
                                        'data' => [
                                            'userid' => $userid,
                                            'single_use' => true 
                                        ]
                                    ];

                                    $newToken = JWT::encode($payload, $key, 'HS256');

                                   
                                    $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                                    $stat = $conn->prepare($sql);
                                    $stat->execute([':token' => $newToken, ':userid' => $userid]);

                                    $response->getBody()->write(json_encode(array(
                                        "status" => "success",
                                        "message" => "User updated successfully.",
                                        "new_token" => $newToken 
                                    )));
                                } else {
                                    $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "User not found or no change made"))));
                                }
                            } else {
                                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "No data to update"))));
                            }
                        } else {
                            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "User ID required"))));
                        }
                    } else {
                        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token already used "))));
                    }
                } else {
                    $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token is not single-use or invalid"))));
                }
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid or expired token"))));
            }
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Token required"))));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Database Error"))));
    }

    return $response;
});

$app->post('/user/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $userid = isset($data->userid) ? $data->userid : null;  
    $token = isset($data->token) ? $data->token : null;  
    $key = 'server_secret_key';

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                    
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                       
                        if ($userid) {
                            $sql = "DELETE FROM users WHERE userid = :userid";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':userid' => $userid]);

                            if ($stat->rowCount() > 0) {
                                
                                $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':userid' => $decoded->data->userid]);

                                
                                $iat = time();
                                $exp = $iat + 300; 
                                $payload = [
                                    'iss' => 'http://library.com',
                                    'iat' => $iat,
                                    'exp' => $exp,
                                    'data' => [
                                        'userid' => $decoded->data->userid,
                                        'single_use' => true 
                                    ]
                                ];

                                $newToken = JWT::encode($payload, $key, 'HS256');

                                
                                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':token' => $newToken, ':userid' => $decoded->data->userid]);

                                
                                $response->getBody()->write(json_encode(array(
                                    "status" => "success",
                                    "data" => array(
                                        "userid" => $userid,
                                        "new_token" => $newToken 
                                    )
                                )));
                            } else {
                                
                                $response->getBody()->write(json_encode(array(
                                    "status" => "fail",
                                    "data" => array("title" => "User not found")
                                )));
                            }
                        } else {
                            
                            $response->getBody()->write(json_encode(array(
                                "status" => "fail",
                                "data" => array("title" => "User ID required")
                            )));
                        }
                    } else {
                        
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Token already used ")
                        )));
                    }
                } else {
                    
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Token is not single-use or invalid")
                    )));
                }
            } catch (Exception $e) {
                
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->post('/author/add', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $authorname = isset($data->authorname) ? $data->authorname : null;
    $token = isset($data->token) ? $data->token : null;

    $key = 'server_secret_key';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                // Decode and validate the token
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Ensure the token is single-use
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                    // Verify the token from the database
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                        // Proceed to add the author if the name is provided
                        if ($authorname) {
                            $sql = "INSERT INTO authors (authorname) VALUES (:authorname)";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':authorname' => $authorname]);

                            // Get the newly added author ID
                            $authorId = $conn->lastInsertId();

                            // Invalidate the used token by setting it to NULL
                            $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':userid' => $decoded->data->userid]);

                            // Generate a new single-use token
                            $iat = time();
                            $exp = $iat + 300; // Token expires in 5 minutes (300 seconds)
                            $payload = [
                                'iss' => 'http://library.com',
                                'iat' => $iat,
                                'exp' => $exp,
                                'data' => [
                                    'userid' => $decoded->data->userid,
                                    'single_use' => true // Mark as single-use token
                                ]
                            ];

                            $newToken = JWT::encode($payload, $key, 'HS256');

                            // Store the new token in the database
                            $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':token' => $newToken, ':userid' => $decoded->data->userid]);

                            
                            $response->getBody()->write(json_encode(array(
                                "status" => "success",
                                "data" => array(
                                    "authorid" => $authorId,
                                    "authorname" => $authorname,
                                    "new_token" => $newToken 
                                )
                            )));
                        } else {
                            // Author name not provided
                            $response->getBody()->write(json_encode(array(
                                "status" => "fail",
                                "data" => array("title" => "Author name required")
                            )));
                        }
                    } else {
                        // Token already used or invalid
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Token already used")
                        )));
                    }
                } else {
                    // Token is not single-use or invalid
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Token is not single-use or invalid")
                    )));
                }
            } catch (Exception $e) {
                // Invalid or expired token
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // Token not provided
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        // Database error
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->post('/author/update', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $authorid = isset($data->authorid) ? $data->authorid : null;
    $authorname = isset($data->authorname) ? $data->authorname : null;
    $token = isset($data->token) ? $data->token : null;

    $key = 'server_secret_key';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                // Decode and validate the token
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Check if the token is marked as single-use
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                    // Verify if the token is still valid and hasn't been used
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                        // Proceed with author update if both authorid and authorname are provided
                        if ($authorid && $authorname) {
                            $sql = "UPDATE authors SET authorname = :authorname WHERE authorid = :authorid";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':authorname' => $authorname, ':authorid' => $authorid]);

                            if ($stat->rowCount() > 0) {
                                // Invalidate the old token after successful update
                                $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':userid' => $decoded->data->userid]);

                                // Generate a new single-use token
                                $iat = time();
                                $exp = $iat + 300; // Token expires in 5 minutes
                                $payload = [
                                    'iss' => 'http://library.com',
                                    'iat' => $iat,
                                    'exp' => $exp,
                                    'data' => [
                                        'userid' => $decoded->data->userid,
                                        'single_use' => true // Mark as single-use
                                    ]
                                ];

                                $newToken = JWT::encode($payload, $key, 'HS256');

                                // Store the new token in the database
                                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':token' => $newToken, ':userid' => $decoded->data->userid]);

                                // Return success response with new token
                                $response->getBody()->write(json_encode(array(
                                    "status" => "success",
                                    "data" => array(
                                        "authorid" => $authorid,
                                        "authorname" => $authorname,
                                        "new_token" => $newToken // Return the new token
                                    )
                                )));
                            } else {
                                // Author not found or no changes made
                                $response->getBody()->write(json_encode(array(
                                    "status" => "fail",
                                    "data" => array("title" => "Author not found or no changes made")
                                )));
                            }
                        } else {
                            // Author ID or new name not provided
                            $response->getBody()->write(json_encode(array(
                                "status" => "fail",
                                "data" => array("title" => "Author ID and new author name required")
                            )));
                        }
                    } else {
                        // Token already used or invalid
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Token already used or invalid")
                        )));
                    }
                } else {
                    // Token is not single-use or invalid
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Token is not single-use or invalid")
                    )));
                }
            } catch (Exception $e) {
                // Invalid or expired token
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // Token not provided
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        // Database error
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->post('/author/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $authorid = isset($data->authorid) ? $data->authorid : null;
    $token = isset($data->token) ? $data->token : null;

    $key = 'server_secret_key';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                // Decode and validate the token
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Check if the token is marked as single-use
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                    // Verify if the token is still valid and hasn't been used
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                        // Proceed with deleting the author if authorid is provided
                        if ($authorid) {
                            $sql = "DELETE FROM authors WHERE authorid = :authorid";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':authorid' => $authorid]);

                            if ($stat->rowCount() > 0) {
                                // Invalidate the old token after successful deletion
                                $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':userid' => $decoded->data->userid]);

                                // Generate a new single-use token
                                $iat = time();
                                $exp = $iat + 300; // Token expires in 5 minutes
                                $payload = [
                                    'iss' => 'http://library.com',
                                    'iat' => $iat,
                                    'exp' => $exp,
                                    'data' => [
                                        'userid' => $decoded->data->userid,
                                        'single_use' => true // Mark as single-use
                                    ]
                                ];

                                $newToken = JWT::encode($payload, $key, 'HS256');

                                // Store the new token in the database
                                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':token' => $newToken, ':userid' => $decoded->data->userid]);

                                // Return success response with the new token
                                $response->getBody()->write(json_encode(array(
                                    "status" => "success",
                                    "data" => array(
                                        "authorid" => $authorid,
                                        "new_token" => $newToken // Return the new token
                                    )
                                )));
                            } else {
                                // Author not found
                                $response->getBody()->write(json_encode(array(
                                    "status" => "fail",
                                    "data" => array("title" => "Author not found")
                                )));
                            }
                        } else {
                            // Author ID not provided
                            $response->getBody()->write(json_encode(array(
                                "status" => "fail",
                                "data" => array("title" => "Author ID required")
                            )));
                        }
                    } else {
                        // Token already used or invalid
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Token already used or invalid")
                        )));
                    }
                } else {
                    // Token is not single-use or invalid
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Token is not single-use or invalid")
                    )));
                }
            } catch (Exception $e) {
                // Invalid or expired token
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // Token not provided
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        // Database error
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->post('/book/add', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $title = isset($data->title) ? $data->title : null;
    $authorid = isset($data->authorid) ? $data->authorid : null;  
    $token = isset($data->token) ? $data->token : null;  

    $key = 'server_secret_key';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
               
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                    
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                       
                        if ($title && $authorid) {
                            $sqlCheckAuthor = "SELECT * FROM authors WHERE authorid = :authorid";
                            $statCheckAuthor = $conn->prepare($sqlCheckAuthor);
                            $statCheckAuthor->execute([':authorid' => $authorid]);

                            if ($statCheckAuthor->rowCount() > 0) {
                                
                                $sql = "INSERT INTO books (title, authorid) VALUES (:title, :authorid)";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':title' => $title, ':authorid' => $authorid]);

                                
                                $bookId = $conn->lastInsertId();

                                
                                $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':userid' => $decoded->data->userid]);

                                
                                $iat = time();
                                $exp = $iat + 300; 
                                $payload = [
                                    'iss' => 'http://library.com',
                                    'iat' => $iat,
                                    'exp' => $exp,
                                    'data' => [
                                        'userid' => $decoded->data->userid,
                                        'single_use' => true 
                                    ]
                                ];

                                $newToken = JWT::encode($payload, $key, 'HS256');

                                
                                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':token' => $newToken, ':userid' => $decoded->data->userid]);

                                
                                $response->getBody()->write(json_encode(array(
                                    "status" => "success",
                                    "data" => array(
                                        "bookid" => $bookId,
                                        "title" => $title,
                                        "authorid" => $authorid,
                                        "new_token" => $newToken 
                                    )
                                )));
                            } else {
                                
                                $response->getBody()->write(json_encode(array(
                                    "status" => "fail",
                                    "data" => array("title" => "Author ID not found")
                                )));
                            }
                        } else {
                            
                            $response->getBody()->write(json_encode(array(
                                "status" => "fail",
                                "data" => array("title" => "Title and Author ID required")
                            )));
                        }
                    } else {
                        
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Token already used")
                        )));
                    }
                } else {
                    
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Token is not single-use or invalid")
                    )));
                }
            } catch (Exception $e) {
                
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
       
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->get('/author/displaybook', function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();
    $authorid = isset($queryParams['authorid']) ? $queryParams['authorid'] : null;
    $token = isset($queryParams['token']) ? $queryParams['token'] : null; // Get token from query parameters

    $key = 'server_secret_key';
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                // Decode and validate the token
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Check if the token is marked as single-use
                if (isset($decoded->data->single_use) && $decoded->data->single_use === true) {
                    // Verify if the token is still valid and hasn't been used
                    $sql = "SELECT token FROM users WHERE userid = :userid AND token = :token";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $decoded->data->userid, ':token' => $token]);
                    $validToken = $stat->fetch();

                    if ($validToken) {
                        // Proceed to retrieve books if authorid is provided
                        if ($authorid) {
                            $sql = "SELECT * FROM books WHERE authorid = :authorid";
                            $stat = $conn->prepare($sql);
                            $stat->execute([':authorid' => $authorid]);

                            $books = $stat->fetchAll(PDO::FETCH_ASSOC);

                            if ($books) {
                                // Invalidate the old token after successful retrieval
                                $sql = "UPDATE users SET token = NULL WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':userid' => $decoded->data->userid]);

                                // Generate a new single-use token
                                $iat = time();
                                $exp = $iat + 300; // Token expires in 5 minutes
                                $payload = [
                                    'iss' => 'http://library.com',
                                    'iat' => $iat,
                                    'exp' => $exp,
                                    'data' => [
                                        'userid' => $decoded->data->userid,
                                        'single_use' => true // Mark as single-use
                                    ]
                                ];

                                $newToken = JWT::encode($payload, $key, 'HS256');

                                // Store the new token in the database
                                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                                $stat = $conn->prepare($sql);
                                $stat->execute([':token' => $newToken, ':userid' => $decoded->data->userid]);

                                // Return success response with book details and new token
                                $response->getBody()->write(json_encode(array(
                                    "status" => "success",
                                    "data" => $books,
                                    "new_token" => $newToken // Return the new token
                                )));
                            } else {
                                // No books found for the given author ID
                                $response->getBody()->write(json_encode(array(
                                    "status" => "fail",
                                    "data" => array("title" => "No books found for the given author ID")
                                )));
                            }
                        } else {
                            // Author ID required
                            $response->getBody()->write(json_encode(array(
                                "status" => "fail",
                                "data" => array("title" => "Author ID required")
                            )));
                        }
                    } else {
                        // Token already used or invalid
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Token already used or invalid")
                        )));
                    }
                } else {
                    // Token is not single-use or invalid
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Token is not single-use or invalid")
                    )));
                }
            } catch (Exception $e) {
                // Invalid or expired token
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // Token not provided
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        // Database error
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->get('/displayAllBooks', function (Request $request, Response $response, array $args) {
    
    $queryParams = $request->getQueryParams();
    $token = isset($queryParams['token']) ? $queryParams['token'] : null;

    $key = 'server_secret_key';

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                // Decode the token
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Fetch all books
                $sql = "SELECT * FROM books";
                $stat = $conn->prepare($sql);
                $stat->execute();

                $books = $stat->fetchAll(PDO::FETCH_ASSOC);

                if ($books) {
                    // Generate a new single-use token
                    $newToken = JWT::encode(
                        ['data' => ['single_use' => true, 'timestamp' => time()]],
                        $key,
                        'HS256'
                    );

                    $response->getBody()->write(json_encode(array(
                        "status" => "success",
                        "data" => $books,
                        "new_token" => $newToken  // Include the new token in the response
                    )));
                } else {
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "No books found")
                    )));
                }
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->post('/book/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookid = isset($data->bookid) ? $data->bookid : null;  
    $token = isset($data->token) ? $data->token : null;  

    $key = 'server_secret_key';

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

               
                if ($bookid) {
                    
                    $sql = "DELETE FROM books WHERE bookid = :bookid";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':bookid' => $bookid]);

                    if ($stat->rowCount() > 0) {
                       
                        $newToken = JWT::encode(
                            ['data' => ['single_use' => true, 'timestamp' => time()]],
                            $key,
                            'HS256'
                        );

                        $response->getBody()->write(json_encode(array(
                            "status" => "success",
                            "data" => array(
                                "bookid" => $bookid,
                                "new_token" => $newToken  
                            )
                        )));
                    } else {
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "Book not found")
                        )));
                    }
                } else {
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "Book ID required")
                    )));
                }
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->post('/user/borrow', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $userid = isset($data->userid) ? $data->userid : null; 
    $bookid = isset($data->bookid) ? $data->bookid : null; 
    $token = isset($data->token) ? $data->token : null; 

    $key = 'server_secret_key';

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($token) {
            try {
                // Decode the token
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Check if the required fields are provided
                if ($userid && $bookid) {
                    // Insert borrow record
                    $sql = "INSERT INTO books_borrowed (userid, bookid, borrowed_at) VALUES (:userid, :bookid, NOW())";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':userid' => $userid, ':bookid' => $bookid]);

                    // Get the new borrow ID
                    $borrowId = $conn->lastInsertId();

                    // Generate a new single-use token
                    $newToken = JWT::encode(
                        ['data' => ['single_use' => true, 'timestamp' => time()]],
                        $key,
                        'HS256'
                    );

                    // Return success response with new token
                    $response->getBody()->write(json_encode(array(
                        "status" => "success",
                        "data" => array(
                            "borrowid" => $borrowId,
                            "userid" => $userid,
                            "bookid" => $bookid,
                            "new_token" => $newToken  // Include the new token
                        )
                    )));
                } else {
                    // Missing User ID or Book ID
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "User ID and Book ID are required")
                    )));
                }
            } catch (Exception $e) {
                // Invalid or expired token
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // No token provided
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->get('/user/borrowed_returned_books', function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();
    $token = isset($queryParams['token']) ? $queryParams['token'] : null;

    $key = 'server_secret_key';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if a token is provided
        if ($token) {
            try {
                // Decode the token (assuming HS256 for simplicity)
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Query to display borrowed and returned books
                $sql = "
                    SELECT u.userid, u.username, bb.borrowid, b.title, a.authorname, bb.borrowed_at, bb.returned_at 
                    FROM books_borrowed AS bb
                    JOIN books AS b ON bb.bookid = b.bookid
                    JOIN authors AS a ON b.authorid = a.authorid
                    JOIN users AS u ON bb.userid = u.userid
                    ORDER BY bb.borrowed_at DESC
                ";
                $stat = $conn->prepare($sql);
                $stat->execute();

                $booksStatus = $stat->fetchAll(PDO::FETCH_ASSOC);

                if ($booksStatus) {
                    // Generate a new token after success
                    $newToken = JWT::encode(
                        ['data' => ['single_use' => true, 'timestamp' => time()]],
                        $key,
                        'HS256'
                    );

                    // Return success with new token
                    $response->getBody()->write(json_encode(array(
                        "status" => "success",
                        "data" => $booksStatus,
                        "new_token" => $newToken  // Include the new token in the response
                    )));
                } else {
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "No borrowed or returned books found")
                    )));
                }
            } catch (Exception $e) {
                // Handle token errors
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // No token provided
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error: " . $e->getMessage())
        )));
    }

    return $response;
});

$app->post('/user/return', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $userid = isset($data->userid) ? $data->userid : null;
    $borrowid = isset($data->borrowid) ? $data->borrowid : null;
    $token = isset($data->token) ? $data->token : null;

    $key = 'server_secret_key';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if a token is provided
        if ($token) {
            try {
                // Decode the token (assuming HS256 for simplicity)
                $decoded = JWT::decode($token, new Key($key, 'HS256'));

                // Check if user ID and borrow ID are provided
                if ($userid && $borrowid) {
                    // Update the record to mark the book as returned
                    $sql = "UPDATE books_borrowed SET returned_at = NOW() WHERE borrowid = :borrowid AND userid = :userid";
                    $stat = $conn->prepare($sql);
                    $stat->execute([':borrowid' => $borrowid, ':userid' => $userid]);

                    // If a record was updated
                    if ($stat->rowCount() > 0) {
                        // Generate a new single-use token after success
                        $newToken = JWT::encode(
                            ['data' => ['single_use' => true, 'timestamp' => time()]],
                            $key,
                            'HS256'
                        );

                        // Return success with new token
                        $response->getBody()->write(json_encode(array(
                            "status" => "success",
                            "data" => array(
                                "borrowid" => $borrowid,
                                "userid" => $userid
                            ),
                            "new_token" => $newToken  // Include the new token in the response
                        )));
                    } else {
                        // If no record was updated
                        $response->getBody()->write(json_encode(array(
                            "status" => "fail",
                            "data" => array("title" => "No borrow record found for the given user ID and borrow ID")
                        )));
                    }
                } else {
                    // If user ID or borrow ID are missing
                    $response->getBody()->write(json_encode(array(
                        "status" => "fail",
                        "data" => array("title" => "User ID and Borrow ID are required")
                    )));
                }
            } catch (Exception $e) {
                // Handle token errors (invalid or expired token)
                $response->getBody()->write(json_encode(array(
                    "status" => "fail",
                    "data" => array("title" => "Invalid or expired token")
                )));
            }
        } else {
            // If token is missing
            $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Token required")
            )));
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response->getBody()->write(json_encode(array(
            "status" => "fail",
            "data" => array("title" => "Database Error")
        )));
    }

    return $response;
});

$app->run();
?>