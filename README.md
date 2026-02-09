# 🎮 OnlineGames

**OnlineGames** is a small open-source web project that collects simple, interactive browser-based games and quizzes in one place.  
The goal of the project is to experiment with game mechanics, quizzes, and modern web technologies while keeping the codebase clean and easy to understand.

This repository is also used as a learning and playground project for frontend development, API integration, and open-source collaboration.

---

## ✨ Features

- 🧠 Quiz-based games
- 👤 User-created quizzes
- ✏️ Edit & delete quizzes (owner only)
- 🔐 Authentication-aware UI
- ⚛️ Built with React & TypeScript
- 🌐 Designed for browser-based gameplay

---

## 🛠 Tech Stack

- **Frontend:** React + TypeScript
- **Routing:** React Router
- **State management:** React hooks
- **Styling:** CSS
- **Backend / API:** PHP based API

---



## 🗄 Database schema

```mermaid
erDiagram
    USERS {
        INT ID PK
        VARCHAR EMAIL
        VARCHAR NAME
        TIMESTAMP CREATED_AT
    }

    QUIZ {
        UUID ID PK
        VARCHAR SLUG
        VARCHAR TITLE
        TEXT DESCRIPTION
        SMALLINT DIFFICULTY
        BOOLEAN IS_PUBLISHED
        BOOLEAN IS_PUBLIC
        TIMESTAMP CREATED_AT
        TIMESTAMP UPDATED_AT
        INT CREATED_BY FK
    }

    QUESTION {
        UUID ID PK
        UUID QUIZ_ID FK
        VARCHAR TYPE
        TEXT QUESTION_TEXT
        INT ORDER_INDEX
        TIMESTAMP CREATED_AT
    }

    ANSWER_OPTION {
        UUID ID PK
        UUID QUESTION_ID FK
        TEXT LABEL
        BOOLEAN IS_CORRECT
        INT ORDER_INDEX
    }

    MATCHING_LEFT_ITEM {
        CHAR ID PK
        UUID QUESTION_ID FK
        TEXT TEXT
        INT ORDER_INDEX
    }

    MATCHING_RIGHT_ITEM {
        CHAR ID PK
        UUID QUESTION_ID FK
        TEXT TEXT
        INT ORDER_INDEX
    }

    MATCHING_PAIR {
        CHAR ID PK
        UUID QUESTION_ID FK
        CHAR LEFT_ID FK
        CHAR RIGHT_ID FK
    }

    QUIZ_ATTEMPT {
        UUID ID PK
        UUID QUIZ_ID FK
        INT USER_ID FK
        INT SCORE
        INT MAX_SCORE
        INT DURATION_SEC
        DATETIME CREATED_AT
    }

    QUIZ_VIEWER_EMAIL {
        UUID QUIZ_ID FK
        VARCHAR USER_EMAIL
        TIMESTAMP CREATED_AT
    }

    USERS ||--o{ QUIZ : creates
    USERS ||--o{ QUIZ_ATTEMPT : attempts

    QUIZ ||--o{ QUESTION : contains
    QUESTION ||--o{ ANSWER_OPTION : has

    QUESTION ||--o{ MATCHING_LEFT_ITEM : has
    QUESTION ||--o{ MATCHING_RIGHT_ITEM : has
    QUESTION ||--o{ MATCHING_PAIR : defines

    MATCHING_LEFT_ITEM ||--o{ MATCHING_PAIR : left
    MATCHING_RIGHT_ITEM ||--o{ MATCHING_PAIR : right

    QUIZ ||--o{ QUIZ_ATTEMPT : results_in
    QUIZ ||--o{ QUIZ_VIEWER_EMAIL : viewed_by
