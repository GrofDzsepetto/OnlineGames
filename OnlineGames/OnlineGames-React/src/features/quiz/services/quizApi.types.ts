export type ApiResponse<T> = {
  success: boolean;
  data?: T;
  message?: string;
  error?: string;
};

export type ApiAnswer = {
  answer_text?: string;
  label?: string;
  text?: string;
  is_correct?: number | string | boolean;
  isCorrect?: boolean;
};

export type ApiGroup = {
  id?: string;
  left: string;
  right: string[];
};

export type ApiQuestion = {
  id?: string;
  question_text?: string;
  question?: string;
  type: "MULTIPLE_CHOICE" | "MATCHING";
  answers?: ApiAnswer[];
  groups?: ApiGroup[];
  pairs?: Array<{
    left: string;
    rights: string[];
  }>;
};

export type ApiQuiz = {
  id?: string;
  quiz_id?: string;
  slug?: string;
  title: string;
  description?: string | null;
  created_by?: string;
  creator_name?: string;
  language_code?: string;
  language?: string;
  is_public?: number | boolean;
  isPublic?: boolean;
  viewer_emails?: string[];
  viewerEmails?: string[];
  questions?: ApiQuestion[];
};

export type ApiQuizResponse = ApiResponse<{
  quiz: ApiQuiz;
}>;