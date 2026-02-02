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
