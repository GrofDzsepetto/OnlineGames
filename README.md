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
    QUIZ {
        UUID ID PK
        VARCHAR SLUG
        VARCHAR TITLE
        TEXT DESCRIPTION
        SMALLINT DIFFICULTY
        BOOLEAN IS_PUBLISHED
        TIMESTAMP CREATED_AT
        TIMESTAMP UPDATED_AT
    }

    QUESTION {
        UUID ID PK
        UUID QUIZ_ID FK
        ENUM TYPE
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

    MATCHING_PAIR {
        UUID ID PK
        UUID QUESTION_ID FK
        TEXT LEFT_TEXT
        TEXT RIGHT_TEXT
    }

    QUIZ ||--o{ QUESTION : contains
    QUESTION ||--o{ ANSWER_OPTION : has
    QUESTION ||--o{ MATCHING_PAIR : has
