interface Props {
  onClick?: () => void;
  children: React.ReactNode;
  className?: string;
  disabled?: boolean;
}

const Button = ({ onClick, children, className = "", disabled }: Props) => (
  <button
    onClick={onClick}
    disabled={disabled}
    className={`btn ${className}`}
  >
    {children}
  </button>
);

export default Button;
