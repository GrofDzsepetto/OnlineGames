export type Quiz = {
  id: string;
  slug: string;
  title: string;
  description: string | null;
  creator_name?: string;
  created_by?: string;
  is_public?: boolean;
  viewer_emails?: string;
  language?: string;
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

