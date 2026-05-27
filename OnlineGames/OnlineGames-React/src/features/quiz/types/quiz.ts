export type Quiz = {
  id: string;
  slug: string;
  title: string;
  description: string | null;
  creator_name?: string;
  created_by: string;
  is_public: boolean;
  language: string;
};

export type QuizAnswer = {
  text: string;
  correct: boolean;
};

export type MatchingPair = {
  left: string;
  rights: string[];
};

export type QuizQuestion = {
  id: string;
  type: "MULTIPLE_CHOICE" | "MATCHING";
  question: string;
  answers?: QuizAnswer[];
  pairs?: MatchingPair[];
};

export type QuizMeta = {
  id: string;
  title: string;
  description: string;
  creator_name?: string;
  isPublic?: boolean;
};

export type QuizResultRow = {
  user_id: string;
  user_name: string;
  score: number;
  max_score: number;
  created_at: string;
};
