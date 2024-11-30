# Library Management System Documentation
This API is designed to manage the core functionality of a library, including user registration and authentication, book management, author management, and the book borrowing and returning system. It's designed to simplify library operations, improve efficiency, and provide a seamless experience for both users and administrators. The API is built using Slim Framework for routing and JWT (JSON Web Tokens) for authentication.

## ⚙️Tools and Software Used
- **PHP**: A robust server-side scripting language for developing dynamic and secure APIs.
- **Slim Framework**: A lightweight PHP framework designed for building RESTful web services with efficiency.
- **JWT (JSON Web Token)**: A standard for secure, stateless authentication and authorization in the API.
- **MySQL**: A powerful relational database system used to manage and store information about users, authors, books, and their relationships.
- **JSON**: A lightweight data format used for seamless communication between the client and the server in API requests and responses.
- **Composer**: Dependency management for PHP projects.

## ✨Features
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

### 🧑🏻‍💻User

#### 📍User Registration
- **URL**: `http://localhost/library/public/user/create`
- **Method**: ``POST``
- **Description**: Registers the user

- **Request Body**:
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


#### 📍User Authentication
- **URL**: `http://localhost/library/public/user/authorize`
- **Method**: ``POST``
- **Description**: Checks if the user is registered then generates a single use token.

- **Request Body**:
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

#### 📍User Update
- **URL**: `http://localhost/library/public/user/update`
- **Method**: ``POST``
- **Description**: Requires token to **Update** the details of the user then generates a single use token.
- **Request Body**:
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
  
  (If token is already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used"
  }
  }
  ```
  
  (If user id is wrong)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "User not found or no change made"
  }
  }
  ```
  
  (If user did not put any token)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token required"
  }
  }
  ```

#### 📍Delete User

- **URL**: `http://localhost/library/public/user/delete`
- **Method**: ``POST``
- **Description**: Requires token to **Delete** user then generates a single use token.
- **Request Body**:
```json
{
  "userid":6,
  "token": "(token)"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": {
    "userid": 6,
    "username":"admin",
    "new_token": "(token)"
    }
  }
  ```

  - **Fail** (If token is invalid or expired)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```


  (If the token is already used)

  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used "
  }
  }
  ```
  
  (If username is not registered)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "User not found"
  }
  }
  ```

### 📚Book

#### 📍Add Book
- **URL**: `http://localhost:/library/public/book/add`
- **Method**: ``POST``
- **Description**: Requires token to **Add** book then generates a single use token.
- **Request Body**:
```json
{
    "title": "Book",
    "authorid": 5,
  "token": "(token)"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": {
    "bookid": "4",
    "title": "Book",
    "authorid": 5,
    "new_token": "(token)"
    }
  }
  ```

  - **Fail** (If wrong Author Id was inputted)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Author ID not found"
  }
  }
  ```
  
  (If Title or Author is empty)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Title and Author ID required"
  }
  }
  ```
  
  (If token is invalid or expired)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```
  
  (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used "
  }
  }
  ```

#### 📍Delete Book

- **URL**: `http://localhost/library/public/book/delete`
- **Method**: ``POST``
- **Description**: Requires token to **Delete** Book then generates a single use token.
- **Request Body**:
```json
{
  "bookid": 1,
  "token": "(token)"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": {
    "bookid": 1,
    "new_token": "(token)"
    }
  }
  ```

  - **Fail**(If Book title is not registered)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Book not found"
  }
  }
  ```
  
  (If book Id is empty)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Book ID required"
  }
  }
  ```
 
  (If token is invalid or expired)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```
  
  (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used "
  }
  }
  ```

#### 📍Display All Books

- **URL**: `http://localhost/library/public/displayAllBooks?token=(token)`
- **Method**: ``GET`` 
- **Description**: Requires token to Display Authors books then generates a single use token.

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": [
    {
      "bookid": 1,
      "title": "The House in The Cerulean Sea",
      "authorid": 2
    },
    {
      "bookid": 2,
      "title": "Under the Whispering Door",
      "authorid": 2
    }
  ],
    "new_token": "(generated token)"
    }
  ```

  - **Fail**(If book database is empty)
  ```json 
  {
  "status": "fail",
  "data": {
    "title": "No books found"
  }
  }
  ```
 
  (If token is invalid or expired)

  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```

   (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used"
  }
  }
  ```

### ✒️Author

#### 📍Add Author

- **URL**: `http://localhost/library/public/author/add`
- **Method**: ``POST``
- **Description**: Requires a token to **Add** Author then generates a single use token.
- **Request Body**:
```json
{
 "authorname": "Author",
  "token": "(token)"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": {
    "authorid": "1",
    "authorname": "Author",
    "new_token": "(token)"
    }
  }
  ```

  - **Fail**(If Author name is empty)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Author name required"
  }
  }
  ```
  
  (If token is invalid or expired)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```
   
   (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used"
  }
  }
  ```

#### 📍Update Author

- **URL**: `http://localhost/library/public/author/update`
- **Method**: ``POST``
- **Description**: Requires token to **Update** Author then generates a single use token.
- **Request Body**:
```json
{
  "authorid": 1,
  "authorname":"Dan Brown",
  "token": "(token)"
}
```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": {
    "authorid": 1,
    "authorname": "Dan Brown",
    "new_token": "(token)"
    }
  }
  ```

  - **Fail**(If Author is not registered)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Author not found or no changes made"
  }
  }
  ```
  
  (If token is invalid or expired)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```
 
  (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used or invalid"
  }
  }
  ```

#### 📍Delete Author

- **URL**: `http://localhost/library/public/author/delete`
- **Method**: ``POST``
- **Description**: Requires token to **Delete** Author then generates a single use token.
- **Request Body**:
```json
{
  "authorid": 2,
  "token": "(token)"
}

```

- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": {
    "authorid": 2,
    "new_token": "(token)"
    }
  }
  ```

  - **Fail**(If Author is not registered)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Author not found"
  }
  }
  ```
  
  (If Author Id is empty)

  ```json
  {
  "status": "fail",
  "data": {
    "title": "Author ID required"
  }
  }
  ```
 
  (If token is expired or invalid)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```
  <br>
  (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used"
  }
  }
  ```

#### 📍Display Books Registered to Author

- **URL**: `http://localhost/library/public/author/displaybook?authorid=(AuthorID)&token=(token)`
- **Method**: ``GET``
- **Description**: Reuires token to **Display** Authors books then generates a single use token. 
    
- **Response**:
  - **Success**:
  ```json
  {
  "status": "success",
  "data": [
    {
      "bookid": 1,
      "title": "The House in The Cerulean Sea",
      "authorid": 2
    },
    {
      "bookid": 2,
      "title": "Under the Whispering Door",
      "authorid": 2
    }
  ],
    "new_token": "(token)"
    }
  ```

  - **Fail**(If no books registered under author)

  ```json 
  {
  "status": "fail",
  "data": {
    "title": "No books found for the given author ID"
  }
  }
  ```
  
  (If Author Id is empty)
  ```json 
  {
  "status": "fail",
  "data": {
    "title": "Author ID required"
  }
  }
  ```
  
  (If token is expired or invalid)

  ```json
  {
  "status": "fail",
  "data": {
    "title": "Invalid or expired token"
  }
  }
  ```
  
  (If token was already used)
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Token already used"
  }
  }
  ```









