# Library Management System Documentation
This API is designed to manage the core functionality of a library, including user registration and authentication, book management, author management, and the book borrowing and returning system. It's designed to simplify library operations, improve efficiency, and provide a seamless experience for both users and administrators. The API is built using Slim Framework for routing and JWT (JSON Web Tokens) for authentication.

## Tools and Software Used
- **PHP**: A robust server-side scripting language for developing dynamic and secure APIs.
- **Slim Framework**: A lightweight PHP framework designed for building RESTful web services with efficiency.
- **JWT (JSON Web Token)**: A standard for secure, stateless authentication and authorization in the API.
- **MySQL**: A powerful relational database system used to manage and store information about users, authors, books, and their relationships.
- **JSON**: A lightweight data format used for seamless communication between the client and the server in API requests and responses.
- **Composer**: Dependency management for PHP projects.

## Features
- **User Registration**: Create a new user account.
- **User Authentication**: Authenticate users and generate JWT tokens for secure access.
- **Token Middleware**: Protect routes with JWT authentication, handle token regeneration, and blacklist expired tokens.
- **CRUD Operations**:
  - Add, update and delete user.
  - Add, Update and delete authors. Dsiplay associated books with authors
  - Add, delete and display all books.
  - Manage relationships between books and authors.
  - Manage borrow and return of books

## Endpoints

### User

#### User Registration
- **URL**: `http://localhost/library/public/user/create`
- **Method**: ``POST``
- **Description**: Registers the user

- **Body**:
```json
{
  "username": "username",
  "password": "password"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "message": "User registered successfully"
  }
  ```


#### User Authentication
- **URL**: `http://localhost/library/public/user/authorize`
- **Method**: ``POST``
- **Description**: Checks if the user is registered then generates a single use token.

- **Body**:
```json
{
  "username":"username",
  "password": "password"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "token": "(token)"
  }
  ```

  - **Fail**
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Authentication Failed"
  }
  }
  ```

#### User Update
- **URL**: `http://localhost/library/public/user/update`
- **Method**: ``POST``
- **Description**: Ask for a token to Update the details of the user. Generates a single use token.
- **Body**:
```json
{
  "userid":"3",
  "username":"updated_username",
  "password":"password",
  "token": "(token)"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "message": "User updated successfully.",
  "new_token": "(token)"
  }
  ```

  - **Fail**
  (For Invalid or expired token)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```

  or 
  (If token is already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used"
  }
  }
  ```
  or 
  ```json
  {
  "status": "fail",
  "data": {
    "title": "User not found or no change made"
  }
  }
  ```
  or 
  (If user did not put any token)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token required"
  }
  }
  ```




