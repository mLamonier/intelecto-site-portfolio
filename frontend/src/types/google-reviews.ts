export interface GoogleReview {
  author: string;
  rating: number;
  text: string;
  time: number;
  relative_time: string;
  profile_photo: string;
}

export interface GoogleReviewsData {
  name: string;
  rating: number;
  total_ratings: number;
  google_url: string;
  reviews: GoogleReview[];
  cached_at: string;
  expires_at: string;
}

export interface GoogleReviewsResponse {
  success: boolean;
  data?: GoogleReviewsData;
  error?: string;
  message?: string;
}
