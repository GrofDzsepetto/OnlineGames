/* =========================================================
   BACKEND → RAW API TYPES
========================================================= */

export type ApiAnswer = {
  answer_text: string;
  is_correct: number | string;
};

export type ApiGroup = {
  id: string;
  left: string[];
  right: string[];
};

export type ApiQuestion = {
  id: string;
  question_text: string;
  type: "MULTIPLE_CHOICE" | "MATCHING";
  answers?: ApiAnswer[];
  groups?: ApiGroup[];
};

export type ApiViewerEmail =
  | { email: string }
  | string;

export type ApiQuiz = {
  id: string;
  title: string;
  description: string | null;
  language_code?: string;
  is_public?: number | boolean;
  viewers_email?: ApiViewerEmail[];
  questions: ApiQuestion[];
};

export type ApiQuizResponse = {
  quiz: ApiQuiz;
};
