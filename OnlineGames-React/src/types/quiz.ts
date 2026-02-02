export type QuizAnswer = {
  text: string;
  correct: boolean;
};
export type QuizQuestion = {
  id: string;
  question: string;
  answers: QuizAnswer[];
};
export type Quiz = {
  id: string;
  slug: string;
  title: string;
  description: string;
};
