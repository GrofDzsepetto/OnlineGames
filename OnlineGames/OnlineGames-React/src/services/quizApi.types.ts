/* =========================================================
   BACKEND → RAW API TYPES
========================================================= */

export type ApiAnswer = {
  ANSWER_TEXT: string;
  IS_CORRECT: number | string;
};

export type ApiGroup = {
  ID: string;
  LEFT: string[];
  RIGHT: string[];
};

export type ApiQuestion = {
  ID: string;
  QUESTION_TEXT: string;
  TYPE: "MULTIPLE_CHOICE" | "MATCHING";
  ANSWERS?: ApiAnswer[];
  GROUPS?: ApiGroup[];
};

export type ApiViewerEmail =
  | { EMAIL: string }
  | string;

export type ApiQuiz = {
  ID: string;
  TITLE: string;
  DESCRIPTION: string | null;
  LANGUAGE_CODE?: string;
  IS_PUBLIC?: number | boolean;
  VIEWER_EMAILS?: ApiViewerEmail[];

  QUESTIONS: ApiQuestion[];
};

export type ApiQuizResponse = {
  QUIZ: ApiQuiz;
};
